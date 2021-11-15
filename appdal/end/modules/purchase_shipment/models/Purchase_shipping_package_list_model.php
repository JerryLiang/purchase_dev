<?php
/**
 * 装箱装柜-装箱明细
 * @time:2020/4/27
 * @author:Dean
 **/

class Purchase_shipping_package_list_model extends Purchase_model
{
    public  $table_name = 'shipment_container_list';
    public $package_list_table = 'shipment_encasement_list';
    public $package_list_detail_table = 'shipment_encasement_detail';
    public $box_num_table  = 'shipment_box_no';
    public $container_log  = 'shipment_container_change_log';
    public $method = '/mrp/overseaShipmentBoxList/saveBoxs';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product/product_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->model('purchase/purchase_order_items_model');
        $this->load->model('compact/compact_items_model');
        $this->load->model('purchase_shipment/shipment_track_list_model');
        $this->load->model('purchase/purchase_order_model');


    }



    /**
     * 获取装柜信息列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     */
    public function get_cabinet_list($params,$offset=null,$limit=null,$page=1)
    {


    //发运类型
    $shipment_arr = [1=>'工厂发运',2=>'中转仓发运'];
    $is_drawback_list = [0=>'否',1=>'是'];
    $is_enable = [1=>'有效',2=>'失效'];
    $cabinet_type_arr = [1=>'20尺',2=>'40尺'];
    $is_package_box = [1=>'是',2=>'否'];
    $order_status_list = getPurchaseStatus();
    $logistics_type_arr  =   $this->get_logistics_type();


    $data_list_temp = [];
    $field='l.*,pre.audit_status,pre.purchase_number,pre.supplier_code,pre.supplier_name,pre.demand_number,pre.es_shipment_time,order.buyer_name,order.warehouse_code,pre.destination_warehouse_code,order.purchase_order_status,order.is_drawback';
    $this->purchase_db->select($field)
        ->from($this->table_name.' l')
        ->join('shipment_track_list pre','pre.new_demand_number=l.new_demand_number','left')
        ->join('purchase_order order','order.purchase_number=pre.purchase_number','left');


    if(isset($params['document_status']) && !empty($params['document_status'])){

        $this->purchase_db->where("l.document_status",$params['document_status']);
    }

    if(isset($params['demand_number']) && !empty($params['demand_number'])){

        $this->purchase_db->group_start();

        $this->purchase_db->or_where('pre.demand_number', $params['demand_number']);
        $this->purchase_db->or_where('pre.new_demand_number', $params['demand_number']);

        $this->purchase_db->group_end();

    }

    if(isset($params['destination_warehouse']) && !empty($params['destination_warehouse'])){ //destination_warehouse
        $this->purchase_db->where_in('pre.destination_warehouse_code',$params['destination_warehouse']);
    }
    if(isset($params['purchase_number']) && !empty($params['purchase_number'])){
        $search_pur=query_string_to_array($params['purchase_number']);
        $this->purchase_db->where_in('pre.purchase_number',$search_pur);
    }

    if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
        $this->purchase_db->where('pre.supplier_code',$params['supplier_code']);
    }

    if(isset($params['shipment_type']) && !empty($params['shipment_type'])){
        $this->purchase_db->where('l.shipment_type',$params['shipment_type']);
    }


    if(isset($params['compact_number']) && !empty($params['compact_number'])){
        $this->purchase_db->join('purchase_compact_items pco','pco.purchase_number=pre.purchase_number','left');

        $this->purchase_db->where('pco.compact_number',$params['compact_number']);
    }

    if(!empty($params['purchase_order_status'])||!empty($params['is_drawback'])){

        if (!empty($params['is_drawback'])) {

            $this->purchase_db->where('order.is_drawback',$params['is_drawback']);

        }
        if (!empty($params['purchase_order_status'])) {
            $this->purchase_db->where('order.purchase_order_status',$params['purchase_order_status']);

        }

    }

    if(isset($params['demand_status']) && !empty($params['demand_status'])){
        $this->purchase_db->join('pur_purchase_suggest su','l.demand_number=su.demand_number','left');

        $this->purchase_db->where('su.suggest_order_status',$params['demand_status']);
    }



    if(isset($params['enable']) && !empty($params['enable'])){

        $this->purchase_db->where('l.enable',$params['enable']);
    }
    if(isset($params['cabinet_type_id']) && !empty($params['cabinet_type_id'])){

        $this->purchase_db->where('l.cabinet_type_id',$params['cabinet_type_id']);
    }


    if(isset($params['virtual_container_sn']) && !empty($params['virtual_container_sn'])){
        $search_sn=query_string_to_array($params['virtual_container_sn']);
        $this->purchase_db->where_in('l.virtual_container_sn',$search_sn);
    }


    if(isset($params['is_package_box']) && !empty($params['is_package_box'])){
        $this->purchase_db->where('l.is_package_box',$params['is_package_box']);
    }

    if(isset($params['warehouse_code']) && !empty($params['warehouse_code'])){
        $this->purchase_db->where_in('order.warehouse_code',$params['warehouse_code']);
    }

    if (isset($params['buyer_id']) and $params['buyer_id']){  // 采购员

        $this->purchase_db->where_in('order.buyer_id',$params['buyer_id']);

    }

    if (isset($params['logistics_type']) and $params['logistics_type']){

        $this->purchase_db->where('l.logistics_type',$params['logistics_type']);

    }

    if (isset($params['destination_warehouse']) and $params['destination_warehouse']){

        $this->purchase_db->where_in('pre.destination_warehouse_code',$params['destination_warehouse']);

    }

    if(isset($params['container_sn']) && !empty($params['container_sn'])){
        $container_sn=query_string_to_array($params['container_sn']);
        $this->purchase_db->where_in('l.container_sn',$container_sn);
    }

    if(isset($params['estimate_date_start']) && !empty($params['estimate_date_start'])){
        $this->purchase_db->where('l.estimate_date>=',$params['estimate_date_start']);
    }

    if(isset($params['estimate_date_end']) && !empty($params['estimate_date_end'])){
        $this->purchase_db->where('l.estimate_date<=',$params['estimate_date_end']);
    }

    if(isset($params['create_time_start']) && !empty($params['create_time_start'])){
        $this->purchase_db->where('l.create_time>=',$params['create_time_start']);
    }

    if(isset($params['create_time_end']) && !empty($params['create_time_start'])){
        $this->purchase_db->where('l.create_time<=',$params['create_time_end']);
    }

    if(isset($params['update_time_start']) && !empty($params['update_time_start'])){
        $this->purchase_db->where('l.update_time>=',$params['update_time_start']);
    }

    if(isset($params['update_time_end']) && !empty($params['update_time_end'])){
        $this->purchase_db->where('l.update_time<=',$params['update_time_end']);
    }


    $clone_db = clone($this->purchase_db);
    $clone_db_other = clone($this->purchase_db);
    $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
    $this->purchase_db=$clone_db;
    $result=$this->purchase_db->order_by('create_time','DESC')->limit($limit,$offset)->get()->result_array();


    $virtual_info = $clone_db_other->select('count(distinct l.virtual_container_sn) as virtual_num')->get()->row_array();




    $this->load->model('warehouse/Warehouse_model');
    $warehouse_list = $this->Warehouse_model->get_warehouse_list();
    $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');


    if (!empty($result)) {

        foreach ($result as $key => $value) {

            $value_temp = [];
            $value_temp['container_sn'] = $value['container_sn'];
            $value_temp['virtual_container_sn'] = $value['virtual_container_sn'];
            $value_temp['shipment_type'] = $shipment_arr[$value['shipment_type']];
            $value_temp['shipment_sn'] = $value['shipment_sn'];
            $value_temp['new_demand_number'] = $value['new_demand_number'];
            $value_temp['sku'] = $value['sku'];
            $value_temp['purchase_number'] = $value['purchase_number'];
                $value_temp['document_status_name'] = $value['document_status_name'];

            //产品信息
            $goods_info = $this->product_model->get_product_info($value['sku']);
            $demand_info = $this->purchase_suggest_model->get_one(0, $value['demand_number']);
            $items_info  = $this->purchase_order_items_model->get_item($value['purchase_number'],$value['sku'],true);

            $value_temp['product_img_url'] = !empty($goods_info['product_img_url']) ? $goods_info['product_img_url'] : '';
            $value_temp['product_name'] = !empty($goods_info['product_name']) ? $goods_info['product_name'] : '';
            $value_temp['purchase_order_status'] = !empty($value['purchase_order_status']) ? $order_status_list[$value['purchase_order_status']] : '';
            $value_temp['demand_status'] = !empty($demand_info['suggest_order_status']) ? $order_status_list[$demand_info['suggest_order_status']] : '';
            $value_temp['supplier_code'] = $value['supplier_code'];
            $value_temp['supplier_name'] = $value['supplier_name'];
            $value_temp['supplier_code'] = $value['supplier_code'];
            $value_temp['buyer_name'] = $value['buyer_name'];
            $value_temp['is_drawback'] = isset($value['is_drawback']) ? $is_drawback_list[$value['is_drawback']] : '';
            $value_temp['total_qty'] = $value['total_qty'];
            //重量计算
            $value_temp['net_weight'] = round($goods_info['net_weight']/1000,2);
            $value_temp['net_weight_total'] = round(($goods_info['net_weight']*$value['total_qty'])/1000,2);
            $value_temp['rought_weight'] = round($goods_info['rought_weight']/1000,2);
            $value_temp['rought_weight_total'] = round(($goods_info['rought_weight']*$value['total_qty'])/1000,2);
            $value_temp['product_volume'] = $goods_info['product_volume'];
            $value_temp['product_volume_total'] = round(($goods_info['product_volume']*$value['total_qty']),2);

            $value_temp['purchase_unit_price'] = !empty($items_info['purchase_unit_price'])?$items_info['purchase_unit_price']:0;
            $value_temp['purchase_price_total'] = !empty($items_info['purchase_unit_price'])?round($items_info['purchase_unit_price']*$value['total_qty'],2):0;

            $value_temp['cabinet_type_id'] = isset($cabinet_type_arr[$value['cabinet_type_id']])?$cabinet_type_arr[$value['cabinet_type_id']]:'' ;

            $value_temp['logistics_type'] = $logistics_type_arr[$value['logistics_type']]??'';

            $value_temp['warehouse_code'] = isset($warehouse_list[$value['warehouse_code']])?$warehouse_list[$value['warehouse_code']]:'';
            $value_temp['destination_warehouse_code'] = isset($warehouse_list[$value['destination_warehouse_code']])?$warehouse_list[$value['destination_warehouse_code']]:'';

            $compact_info  =  $this->compact_items_model->get_compact_by_purchase($value['purchase_number']);
            $value_temp['compact_number'] = !empty($compact_info['compact_number'])?$compact_info['compact_number']:'';

            $value_temp['product_model']  = !empty($goods_info['product_model'])?$goods_info['product_model']:'';
            $value_temp['product_brand']  = !empty($goods_info['product_brand'])?$goods_info['product_brand']:'';

            $value_temp['create_time']  = !empty($value['create_time'])?$value['create_time']:'';
            $value_temp['update_time']  = !empty($value['update_time'])?$value['update_time']:'';
            $value_temp['estimate_date']  = !empty($value['es_shipment_time'])?$value['es_shipment_time']:'';

            $value_temp['enable']  = $value['enable']==1?'有效':'无效';
            $value_temp['is_package_box']  = $value['is_package_box']==1?'是':'否';

            $data_list_temp[] = $value_temp;


        }
    }

    $return_data = [
        'data_list'   => [
            'value' => $data_list_temp,
            'key' =>[
                'ID','虚拟柜号','发运类型\n发运单号','新备货单号','sku','图片','产品名称','备货单状态\n订单状态','供应商名称','采购员','是否退税','总数','净重KG\n总净重KG','毛重KG\n总毛重','单个体积cm³\n总体积cm³',
                '采购单价\n总金额','柜型\n物流类型','采购仓库\n目的仓','合同号','产品型号\n产品品牌','创建时间\nID更新时间','预计发货日期','ID是否有效','是否已生成装箱明细'
            ],
            'drop_down_box' => [
                'user_list' => getBuyerDropdown(),
                'shipment_type'    =>$shipment_arr,
                'purchase_order_status' =>$order_status_list,
                'demand_status' =>$order_status_list,
                'cabinet_type_id' =>$cabinet_type_arr,
                'is_drawback_list'=>$is_drawback_list,
                'is_package_box' => $is_package_box,
                'is_enable' =>$is_enable,
                'warehouse_code'=>$warehouse_list,
                'destination_warehouse'=>$warehouse_list,
                'logistics_type'=>$logistics_type_arr,
                'container_list' => getContainer()


            ]
        ],
        'paging_data' => [
            'total'     => $total,
            'offset'    => $page,
            'limit'     => $limit,
            'virtual_num'=>$virtual_info['virtual_num']??0

        ],

    ];

    return $return_data;
}
//装箱明细列表
    public function get_box_list($params,$offset=null,$limit=null,$page=1){

        //发运类型
        $shipment_arr = [1=>'工厂发运',2=>'中转仓发运'];
        $is_drawback_list = [0=>'否',1=>'是'];
        $is_enable = [1=>'有效',2=>'失效'];
        $cabinet_type_arr = [1=>'20尺',2=>'40尺'];
        $is_package_box = [1=>'是',2=>'否'];
        $order_status_list = getPurchaseStatus();
        $logistics_type  =   $this->get_logistics_type();



        $data_list_temp = [];
        $field='l.*,pre.check_time,pre.es_shipment_time,pre.purchase_number,pre.supplier_name,pre.supplier_code,pre.destination_warehouse_code,con.virtual_container_sn,con.demand_number,con.shipment_sn,con.shipment_type,con.cabinet_type_id,con.update_time as con_update_time,con.enable,order.purchase_order_status,order.is_drawback';
        $this->purchase_db->select($field)
            ->from($this->package_list_table.' l')
            ->join($this->table_name.' con','con.container_sn=l.container_sn','left')
            ->join('shipment_track_list pre','pre.new_demand_number=con.new_demand_number','left')
            ->join('purchase_order order','order.purchase_number=pre.purchase_number','left');


        if(isset($params['demand_number']) && !empty($params['demand_number'])){

            $this->purchase_db->group_start();

            $this->purchase_db->or_where('con.demand_number', $params['demand_number']);
            $this->purchase_db->or_where('con.new_demand_number', $params['demand_number']);

            $this->purchase_db->group_end();

        }
        if(isset($params['purchase_number']) && !empty($params['purchase_number'])){
            $search_pur=query_string_to_array($params['purchase_number']);
            $this->purchase_db->where_in('pre.purchase_number',$search_pur);
        }

        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('pre.supplier_code',$params['supplier_code']);
        }

        if( isset($params['warehouse_code']) && !empty($params['warehouse_code'])){

            $this->purchase_db->where("order.warehouse_code",$params['warehouse_code']);
        }





        if(!empty($params['purchase_order_status'])||!empty($params['is_drawback'])){

            if (!empty($params['is_drawback'])) {

                $this->purchase_db->where('order.is_drawback',$params['is_drawback']);

            }
            if (!empty($params['purchase_order_status'])) {
                $this->purchase_db->where('order.purchase_order_status',$params['purchase_order_status']);

            }

        }

        if(isset($params['demand_status']) && !empty($params['demand_status'])){
            $this->purchase_db->join('pur_purchase_suggest su','con.demand_number=su.demand_number','left');

            $this->purchase_db->where('su.purchase_order_status',$params['demand_status']);
        }



        if(isset($params['cabinet_type_id']) && !empty($params['cabinet_type_id'])){

            $this->purchase_db->where('con.cabinet_type_id',$params['cabinet_type_id']);
        }


        if(isset($params['virtual_container_sn']) && !empty($params['virtual_container_sn'])){
            $search_sn=query_string_to_array($params['virtual_container_sn']);
            $this->purchase_db->where_in('con.virtual_container_sn',$search_sn);
        }


        if(isset($params['is_package_box']) && !empty($params['is_package_box'])){
            $this->purchase_db->where('con.is_package_box',$params['is_package_box']);
        }

        if(isset($params['warehouse_code']) && !empty($params['warehouse_code'])){
            $this->purchase_db->where_in('order.warehouse_code',$params['warehouse_code']);
        }




        if (isset($params['create_user']) and $params['create_user']){  // 采购员

            $this->purchase_db->where_in('l.create_user_id',$params['create_user']);

        }


        if (isset($params['destination_warehouse']) and $params['destination_warehouse']){

            $this->purchase_db->where_in('pre.destination_warehouse_code',$params['destination_warehouse']);

        }

        if(isset($params['container_sn']) && !empty($params['container_sn'])){
            $container_sn=query_string_to_array($params['container_sn']);
            $this->purchase_db->where_in('con.container_sn',$container_sn);
        }

        if(isset($params['estimate_date_start']) && !empty($params['estimate_date_start'])){
            $this->purchase_db->where('pre.es_shipment_time>=',$params['estimate_date_start']);
        }

        if(isset($params['estimate_date_end']) && !empty($params['estimate_date_end'])){
            $this->purchase_db->where('pre.es_shipment_time<=',$params['estimate_date_end']);
        }

        if(isset($params['create_time_start']) && !empty($params['create_time_start'])){
            $this->purchase_db->where('l.create_time>=',$params['create_time_start']);
        }

        if(isset($params['create_time_end']) && !empty($params['create_time_start'])){
            $this->purchase_db->where('l.create_time<=',$params['create_time_end']);
        }

        if(isset($params['check_time_start']) && !empty($params['check_time_start'])){
            $this->purchase_db->where('pre.check_time>=',$params['check_time_start']);
        }
        if(isset($params['check_time_end']) && !empty($params['check_time_end'])){
            $this->purchase_db->where('pre.check_time<=',$params['check_time_end']);
        }


        $clone_db = clone($this->purchase_db);
        $clone_db_other = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数

        $virtual_info = $clone_db_other->select('count(distinct con.virtual_container_sn) as virtual_num,count(distinct l.container_sn) as box_num')->get()->row_array();




        $this->purchase_db=$clone_db;
        $result=$this->purchase_db->order_by('create_time','DESC')->limit($limit,$offset)->get()->result_array();

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');


        if (!empty($result)) {

            foreach ($result as $key => $value) {

                $value_temp = [];
                $value_temp['id']=$value['id'];
                $value_temp['container_sn'] = $value['container_sn'];
                $value_temp['virtual_container_sn'] = $value['virtual_container_sn'];
                $value_temp['shipment_sn'] = $value['shipment_sn'];
                $value_temp['shipment_type'] = $shipment_arr[$value['shipment_type']];
                $value_temp['supplier_name'] = $value['supplier_name'];
                $value_temp['supplier_code'] = $value['supplier_code'];
                $value_temp['purchase_number'] = $value['purchase_number'];
                $value_temp['sku'] = $value['sku'];


                //产品信息
                $goods_info = $this->product_model->get_product_info( $value['sku']);
                $demand_info = $this->purchase_suggest_model->get_one(0, $value['demand_number']);
                $items_info  = $this->purchase_order_items_model->get_item($value['purchase_number'],$value['sku'],true);
                $value_temp['product_img_url'] = !empty($goods_info['product_img_url']) ? $goods_info['product_img_url'] : '';
                $value_temp['product_name'] = !empty($goods_info['product_name']) ? $goods_info['product_name'] : '';
                $value_temp['case_qty'] = $value['case_qty'];
                $value_temp['in_case_qty'] = $value['in_case_qty'];
                $value_temp['total_num'] = $value_temp['case_qty']*$value_temp['in_case_qty'];
                $value_temp['size']  = $value['length'].'*'.$value['width'].'*'.$value['height'];
                $value_temp['net_weight']  = round($goods_info['net_weight']/1000,2);
                $value_temp['rought_weight']  = round($goods_info['rought_weight']/1000,2);
                $value_temp['import_net_weight']  = round($value['net_weight'],2);
                $value_temp['import_rought_weight']  = round($value['rought_weight'],2);
                $value_temp['net_weight_total']  = round($value['net_weight']*$value_temp['total_num'],2);
                $value_temp['rought_weight_total']  = round($value['rought_weight']*$value_temp['total_num'],2);
                $value_temp['purchase_unit_price']  = $items_info['purchase_unit_price'];
                $value_temp['purchase_price_total']  = round($items_info['purchase_unit_price']*$value_temp['total_num'],2);
                $value_temp['is_drawback'] = isset($value['is_drawback']) ? $is_drawback_list[$value['is_drawback']] : '';
                $value_temp['cabinet_type_id'] = $cabinet_type_arr[$value['cabinet_type_id']]??'' ;
                $value_temp['warehouse_code'] = isset($warehouse_list[$value['warehouse_code']])?$warehouse_list[$value['warehouse_code']]:'';
                $value_temp['destination_warehouse_code'] = isset($warehouse_list[$value['destination_warehouse_code']])?$warehouse_list[$value['destination_warehouse_code']]:'';
                $value_temp['check_time'] = $this->time_filter($value['check_time']);
                $value_temp['estimate_date'] = $this->time_filter($value['es_shipment_time']);
                $value_temp['order_status'] = !empty($order_status_list[$value['purchase_order_status']])?$order_status_list[$value['purchase_order_status']]:'';
                $value_temp['suggest_order_status'] = !empty($order_status_list[$demand_info['suggest_order_status']])?$order_status_list[$demand_info['suggest_order_status']]:'';
                $value_temp['create_user_name']  = $value['create_user_name'];
                $value_temp['create_time']       = $this->time_filter($value['create_time']);
                $value_temp['update_time']       = $this->time_filter($value['update_time']);
                $value_temp['con_update_time']   = $this->time_filter($value['con_update_time']);
                $value_temp['enable']   = $is_enable[$value['enable']];


                $data_list_temp[] = $value_temp;



            }
        }

        $return_data = [
            'data_list'   => [
                'value' => $data_list_temp,
                'key' =>[
                    'ID','虚拟柜号\n发运单号','发运类型','供应商名称','采购单号\nsku','图片','产品名称','箱数','箱内数','总数','外箱尺寸cm','净重KG\n净重KG-导入','毛重KG\n毛重KG-导入','总净重\n总毛重','含税单价\n总金额','是否退税\n柜型','采购仓库\n目的仓'
                ],
                'drop_down_box' => [
                    'user_list' => getBuyerDropdown(),
                    'shipment_type'    =>$shipment_arr,
                    'purchase_order_status' =>$order_status_list,
                    'demand_status' =>$order_status_list,
                    'cabinet_type_id' =>$cabinet_type_arr,
                    'is_drawback_list'=>$is_drawback_list,
                    'is_package_box' => $is_package_box,
                    'is_enable' =>$is_enable,
                    'warehouse_code'=>$warehouse_list,
                    'destination_warehouse'=>$warehouse_list,
                    'logistics_type'=>$logistics_type



                ]
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit,
                'virtual_num'=>$virtual_info['virtual_num']??0,
                'box_num'=>$virtual_info['box_num']??0


            ]
        ];
        return $return_data;
    }


    /**
     * 导入装箱明细
     * @author dean
     * @param $data
     * @return array
     */
    public function import_package_list($data){

        $return = ['code' => true,'data' => [],'message' => ''];

        $container_sn_arr = [];//按装箱id分组
        $error_list = [];
        $send_java_data=[];//传入java装箱数据
        $drawbackMap = [0=>2,1=>1];

        if($data){

            foreach($data as $key => $value){
                if($key <= 1) continue;
                $error_str = [];
                $container_sn                = isset($value['A']) ? trim($value['A']) : '';
                $case_qty                    =isset($value['B']) ? trim($value['B']) : 0;//箱数
                $in_case_qty                 =isset($value['C']) ? trim($value['C']) : 0;//箱内数量
                $length                      =isset($value['D']) ? trim($value['D']) : 0;//长度
                $width                       =isset($value['E']) ? trim($value['E']) : 0;//宽度
                $height                      =isset($value['F']) ? trim($value['F']) : 0;//高度
                $net_weight                  =isset($value['G']) ? trim($value['G']) : 0;//净重
                $rought_weight               =isset($value['H']) ? trim($value['H']) : 0;//毛重

                if (!($case_qty>0&&(floor($case_qty)==$case_qty))) {
                    $error_str[] ='箱数必须为正整数';
                }

                if (!($in_case_qty>0&&(floor($in_case_qty)==$in_case_qty))) {
                    $error_str[] ='箱内数必须为正整数';
                }

                if ($length<=0) {
                    $error_str[] ='长度格式不正确';
                }

                if ($width<=0) {
                    $error_str[] ='宽度格式不正确';
                }

                if ($height<=0) {
                    $error_str[] ='高度格式不正确';
                }
                if ($net_weight<=0) {
                    $error_str[] ='净重格式不正确';

                }
                if ($rought_weight<=0) {
                    $error_str[] ='毛重格式不正确';

                }

                $container_sn = strtoupper($container_sn);

                if (!empty($error_str)) {
                    !isset($error_list[$container_sn])&&$error_list[$container_sn]=[];
                   $error_list[$container_sn] = array_merge($error_list[$container_sn],$error_str);

                }
                $container_sn_arr[$container_sn][]=[
                        'container_sn'=>$container_sn,
                        'case_qty'=>$case_qty,
                        'in_case_qty'=>$in_case_qty,
                        'length'=>$length,
                        'width'=>$width,
                        'height'=>$height,
                        'net_weight'=>$net_weight,
                        'rought_weight'=>$rought_weight

                    ];



            }


            //验证
            if (!empty($container_sn_arr)) {
                foreach ($container_sn_arr as $container_sn=>$container_data) {
                    //获取柜型id信息
                    $error_str = [];
                    $container_info =$this->get_container_info(['container_sn'=>$container_sn]);

                    if (empty($container_info)) {
                        $error_str[] = 'ID不存在';

                    } else {
                        if ($container_info['enable'] == 2) {
                            $error_str[] = 'ID无效';

                        }

                    }
                    $track_info = $this->shipment_track_list_model->get_track_by_demand(null,$container_info['new_demand_number']);




                    if (empty($track_info))
                    {

                        $error_str[] = '采购单信息不存在';

                    }


                    //获取采购单信息
                    if (!empty($track_info)) {

                        $purchase_item_info = $this->purchase_order_items_model->get_item($track_info['purchase_number'],$track_info['sku'],true);
                        $demand_info_item = $this->purchase_suggest_model->get_one(0,$track_info['demand_number']);
                        $order_info       = $this->purchase_order_model->get_one($track_info['purchase_number'],false);



                    }



                    //获取装箱明细
                   /* $package_detail = $this->get_package_detail(['container_sn'=>$container_sn],"row");
                    print_r($package_detail);die();
                    if (!empty($package_detail)) {//如果存在明细
                        $error_str[] = 'ID已经关联装箱明细';

                    }*/

                    $package_detail = $this->get_package_detail(['container_sn'=>$container_sn]);

                    if (!empty($package_detail)) {//如果存在明细
                        $containerdatas = $this->purchase_db->from("shipment_container_list")->where("container_sn", $container_sn)->get()->row_array();
                        if ($containerdatas['document_status'] != 5 &&
                            $containerdatas['document_status'] != 28) {

                            $error_str[] = 'ID已经关联装箱明细';
                        }
                    }

                    //验证数量
                    $total = 0;
                    foreach ($container_data as $detail) {
                        $total+= $detail['case_qty']*$detail['in_case_qty'];

                    }

                    if ($total !=$container_info['total_qty'] ) {//总数数量
                        $error_str[] = 'ID对应总数与装柜明细的总数不一致';

                    }
                    if (empty($error_str)){
                        foreach ($container_data as $detail) {
                            $detail['purchase_number'] = $track_info['purchase_number'];
                            $detail['sku']  = $container_info['sku'];
                            $detail['warehouse_code']   = $order_info['warehouse_code'];
                            $detail['logistics_type']              =$container_info['logistics_type'];
                            $detail['shipment_sn']              =$container_info['shipment_sn'];
                            $detail['price']                    =$purchase_item_info['purchase_unit_price'];
                            $detail['new_demand_number']        =$container_info['new_demand_number'];
                            $detail['supplier_code']            =$track_info['supplier_code'];//供应商编码
                            $detail['is_drawback']              =$drawbackMap[$demand_info_item['is_drawback']??0];

                            $insert_data[$container_sn][]= $detail;


                        }

                    } else {

                        !isset($error_list[$container_sn])&&$error_list[$container_sn]=[];
                        $error_list[$container_sn] = array_merge($error_list[$container_sn],$error_str);

                    }

                }

            }

            if($error_list){

                $error_return=[];
                foreach ($error_list as $con_sn=>$error_data) {
                    $error_return[$con_sn] = array_unique($error_data);

                }
                $return['code'] = false;
                $return['data'] = $error_return;
                return $return;
            }

            try{

                if (empty($insert_data)) {
                    throw new Exception('可导入数据为空');

                }
                $this->purchase_db->trans_begin();




                foreach ($insert_data as $container_sn=>$container_info) {

                    foreach ($container_info as $data) {
                        $plan_data = [];//推送计划系统数据
                        $add_data = [];
                        //$add_data['box_number'] = $this->box_number($data['purchase_number']);
                        $add_data['container_sn'] = $container_sn;
                        $add_data['case_qty']  =   $data['case_qty'];
                        $add_data['in_case_qty']  =   $data['in_case_qty'];
                        $add_data['net_weight']  =   $data['net_weight'];
                        $add_data['rought_weight']  =   $data['rought_weight'];
                        $add_data['create_user_name']  =   getActiveUserName();
                        $add_data['create_time']  =    date('Y-m-d H:i:s');
                        $add_data['sku']          = $data['sku'];
                        $add_data['length']       = $data['length'];
                        $add_data['width']        = $data['width'];
                        $add_data['height']        = $data['height'];
                        $add_data['warehouse_code']        = $data['warehouse_code'];
                        $add_datda['create_user_id'] = getActiveUserId();

                        $historyContainer = $this->purchase_db->from($this->package_list_table)->where("container_sn",$container_sn)
                            ->get()->result_array();

                        if(!empty($historyContainer)){

                            foreach($historyContainer as $history_key=>&$history_value){

                                unset($history_value['id']);
                            }

                            $this->purchase_db->insert_batch("shipment_encasement_list_bak",$historyContainer);
                            $this->purchase_db->from($this->package_list_table)->where("container_sn",$container_sn)->delete();
                        }

                        $re = $this->purchase_db->insert($this->package_list_table, $add_data);
                        $package_id = $this->purchase_db->insert_id($this->package_list_table);


                        if (empty($package_id)) {
                            throw new Exception('导入整箱信息失败');

                        }
                       /*  $box_sn = $this->create_box_sn($data['warehouse_code'],$package_id);

                         $up_re = $this->purchase_db->where('id',$package_id)->update($this->package_list_table, array('box_sn'=>$box_sn));*/


                     /*   if (empty($up_re)) {
                            throw new Exception('生成箱号失败');

                        }*/
                        $plan_data['code'] = '';//兼容箱号
                        $plan_data['shipmentSn'] = $data['shipment_sn'];//发运单号
                        $plan_data['skuTotal']  = $data['case_qty']*$data['in_case_qty'];//总发货数量
                        $plan_data['packBoxId'] = $box_sn;
                        $plan_data['warehouseCode'] = '';
                        $plan_data['destCountry'] = '';
                        $plan_data['shipType'] = '';
                        $plan_data['transitWarehouse'] = '';
                        $plan_data['isDirect'] = 1;
                        $plan_data['drawback'] = $data['is_drawback'];
                        $plan_data['status'] = 3;
                        $plan_data['vat'] = '';
                        $plan_data['isInspection'] = '';
                        $plan_data['isFumigation'] = '';
                        $plan_data['packUsername'] = $add_data['create_user_name'];
                        $plan_data['packTime'] = $add_data['create_time'];
                        $plan_data['weight'] = $add_data['rought_weight'];
                        $plan_data['length'] = $add_data['length'];
                        $plan_data['width'] = $add_data['width'];
                        $plan_data['height'] = $add_data['height'];
                        $plan_data['boxNo'] = $add_data['box_number'];
                        $plan_data['pushFrom'] = 1;
                       

                       // $container_shipment_type = $this->purchase_db->from("")

                        for ($i=1;$i<=$data['case_qty'];$i++) {//生成装箱明细
                            $add_detail = [];
                            $add_detail['sku'] = $data['sku'];
                            $add_detail['box_number'] = $this->box_number($data['shipment_sn']);
                            $add_detail['purchase_number'] = $data['purchase_number'];
                            $add_detail['in_case_qty'] = $data['in_case_qty'];
                            $add_detail['net_weight'] = $data['net_weight'];
                            $add_detail['rought_weight'] = $data['rought_weight'];
                            $add_detail['create_time'] = date('Y-m-d H:i:s');
                            $add_detail['enable'] = 1;
                            $add_detail['box_id'] = $package_id;
                            $add_detail['length'] = $data['length'];
                            $add_detail['width'] = $data['width'];
                            $add_detail['height'] = $data['height'];
                            $add_detail['warehouse_code'] = $data['warehouse_code'];
                            $add_detail['update_time'] =date('Y-m-d H:i:s');
                            $add_detail['container_sn'] =$container_sn;
                            $re = $this->purchase_db->insert($this->package_list_detail_table, $add_detail);
                            $package_detail_id = $this->purchase_db->insert_id($this->package_list_detail_table);
                            if (empty($package_detail_id)) {
                                throw new Exception('导入整箱明细信息失败');
                            }
                            $box_sn = $this->create_box_sn($data['warehouse_code'],$package_detail_id);
                            $box_detail_sn = $this->create_box_sn($data['warehouse_code'],$package_detail_id,'PMSZFBOXMX');
                            $up_re = $this->purchase_db->where('id',$package_detail_id)->update($this->package_list_detail_table, array('box_detail_sn'=>$box_detail_sn,'box_sn'=>$box_sn));
                            if (empty($up_re)) {
                                throw new Exception('生成装箱明细号失败');

                            }
                            //推送计划箱子信息
                            $plan_data['code'] = '';//兼容箱号
                            $plan_data['shipmentSn'] = $data['shipment_sn'];//发运单号
                            $plan_data['skuTotal']  = $data['in_case_qty'];//箱内数
                            $plan_data['packBoxId'] = $box_sn;
                            $plan_data['warehouseCode'] = '';
                            $plan_data['destCountry'] = '';
                            $plan_data['shipType'] = '';
                            $plan_data['transitWarehouse'] = '';
                            $plan_data['isDirect'] = 1;
                            $plan_data['drawback'] = $data['is_drawback'];
                            $plan_data['status'] = 3;
                            $plan_data['vat'] = '';
                            $plan_data['isInspection'] = '';
                            $plan_data['isFumigation'] = '';
                            $plan_data['packUsername'] = $add_data['create_user_name'];
                            $plan_data['packTime'] = $add_data['create_time'];
                            $plan_data['weight'] = $add_data['rought_weight'];
                            $plan_data['length'] = $add_data['length'];
                            $plan_data['width'] = $add_data['width'];
                            $plan_data['height'] = $add_data['height'];
                            $plan_data['boxNo'] = $add_detail['box_number'];
                            $plan_data['pushFrom'] = 1;
                            $plan_data['details'][0]=['boxSkuId'=>$box_detail_sn,'sku'=>$data['sku'],'num'=>$data['in_case_qty'],'price'=>$data['price'],
                                'supplierCode'=>$data['supplier_code'],'purchaseOrderNo'=>$data['purchase_number'],'demandNumber'=>$data['new_demand_number']

                            ];

                            $send_java_data['boxs'][] = $plan_data;


                        }



                    }
                    $is_create_con = $this->purchase_db->where('container_sn',$container_sn)->update($this->tableName(), array('is_package_box'=>1));//更新是否装箱


                }



                $url = SMC_JAVA_API_URL . $this->method;
                $header = array('Content-Type: application/json');
                $access_taken = getOASystemAccessToken();
                $url = $url . "?access_token=" . $access_taken;
                $send_res = getCurlData($url, json_encode($send_java_data, JSON_UNESCAPED_UNICODE), 'post', $header);
                $send_res = json_decode($send_res, true);

                  if (isset($send_res['code'])) {
                      if ($send_res['code']==200) {

                          $this->purchase_db->trans_commit();
                          $return['message']  = '导入成功';

                      } else {
                          throw new Exception('java接口返回错误:'.$send_res['msg']);

                      }

                  } else {
                      throw new Exception('java装箱接口异常，导入失败');

                  }






            }catch(Exception $e){
                $return['code']     = false;
                $return['message']  = $e->getMessage();
                $this->purchase_db->trans_rollback();
            }

            //记录推送日志
            apiRequestLogInsert(
                [
                    'record_type' => 'send_plan_box_detail',
                    'api_url' => isset($url)?$url:'',
                    'post_content' =>json_encode($send_java_data),
                    'response_content' => isset($send_res)?json_encode($send_res):'',
                    'create_time' => date('Y-m-d H:i:s')
                ]);


            return $return;
        }else{
            $return['code']     = false;
            $return['message']  = '数据缺失';
            return $return;
        }
    }

    //通过柜型id获取柜型信息
    public function get_container_info($where=[])
    {
        return $this->purchase_db->select('*')->from($this->tableName())->where($where)->get()->row_array();

    }

    //获取装箱明细
    public function get_package_detail($where=[])
    {
        return $this->purchase_db->select('*')->from($this->package_list_table)->where($where)->get()->result_array();

    }

    //根据采购单生成箱子序列号
    public function box_number($purchase_number,$add_number=1)
    {
        //获取当前id

        $box_number = 1;//初始值为1

        $row = $this->purchase_db->select('num')->from($this->box_num_table)->where('purchase_number',$purchase_number)->get()->row_array();
        if(empty($row)){// 不存在记录则新增记录
            $result = $this->purchase_db->insert($this->box_num_table,['num'=>$box_number,'purchase_number'=>$purchase_number]);
            return $box_number;
        }else{// 存在记录则获取之前的 编号再增加指定值
            $update_data = [
                'num' => $row['num'] + $add_number,
            ];
            $this->purchase_db->set($update_data);
            $this->purchase_db->where('purchase_number', $purchase_number);
            $result = $this->purchase_db->update($this->box_num_table);
            return $row['num'] + $add_number;

        }



    }

    //生成装箱或者装箱明细序列号

    public function create_box_sn($warehouse_code,$package_id,$sn='PMSZFBOX')
    {
        return $warehouse_code.$package_id.$sn;

    }

    //获取柜号对应的整箱明细

    public function get_package_box_list($params)
    {

        $query_builder = $this->purchase_db;
        $query_builder->where('container_sn', $params['container_sn']);
        $result = $query_builder->get($this->package_list_table)->result_array();
        return !empty($result)?array_column($result,'box_sn'):'';
    }

    //通过ids获取打印装箱明细的数据

    public function print_package_box_detail($ids)
    {
        $this->load->model('supplier/supplier_contact_model');
        $is_drawback_list = [0=>'不退税',1=>'退税'];
        $data_list =[];

        if (!empty($ids)) {
            $field='detail.*,pre.purchase_number,pre.supplier_code,pre.supplier_name,pre.sku,con.enable,con.update_time';
            $result = $this->purchase_db->select($field)
                ->from($this->package_list_table.' l')
                ->join($this->package_list_detail_table.' detail','detail.box_id=l.id','inner')
                ->join($this->table_name.' con','con.container_sn=l.container_sn','inner')
                ->join('shipment_track_list pre','pre.new_demand_number=con.new_demand_number','inner')
                ->where_in('l.id',$ids)
                ->get()
                ->result_array();

            if(empty($result)){
                return ['code'=>false,'data'=>'','msg'=>'数据缺失'];
            }

            $supplier_code_arr= array_unique(array_column($result,'supplier_code'));


            if (count($supplier_code_arr)>1) {
                return ['code'=>false,'data'=>'','msg'=>'只能选取同一供应商的装箱数据'];
            }

            $ship_address= $this->purchase_db
                ->select('ship_address')
                ->where('supplier_code',$supplier_code_arr[0])
                ->get('supplier')
                ->row_array();
            if(empty($ship_address)){
                return ['code'=>false,'data'=>'','msg'=>'供应商不存在'];
            }

            $contact_list = $this->supplier_contact_model->get_contact_list($supplier_code_arr[0]);

            $data_list['contact_list'] = !empty($contact_list)?$contact_list[0]:[];
            $data_list['supplier_name'] = $result[0]['supplier_name'];
            $data_list['ship_address']  = $ship_address;



            if (!empty($result)) {
                $this->load->model('product/product_model');
                foreach ($result as $key=>$value) {
                    $goods_info = $this->product_model->get_product_info( $value['sku']);
                    $items_info  = $this->purchase_order_items_model->get_item($value['purchase_number'],$value['sku'],true);
                    $value_temp = [];
                    $value_temp['box_id']=$value['box_id'];
                    $value_temp['box_detail_sn']  = $value['box_detail_sn'];
                    $value_temp['sku'] = $value['sku'];
                    $value_temp['purchase_number'] = $value['purchase_number'];
                    $value_temp['product_img_url'] = !empty($goods_info['product_img_url']) ? $goods_info['product_img_url'] : '';
                    $value_temp['product_name'] = !empty($goods_info['product_name']) ? $goods_info['product_name'] : '';
                    $value_temp['product_model']  = !empty($goods_info['product_model'])?$goods_info['product_model']:'';
                    $value_temp['product_brand']  = !empty($goods_info['product_brand'])?$goods_info['product_brand']:'';
                    $value_temp['in_case_qty'] = $value['in_case_qty'];
                    $value_temp['size']  = $value['length'].'*'.$value['width'].'*'.$value['height'];
                    $value_temp['volume']  = round($value['length']*$value['width']*$value['height'],2)  ;


                    $value_temp['net_weight_per']  = round($value['net_weight']/$value_temp['in_case_qty'],3);
                    $value_temp['rought_weight_per']  = round($value['rought_weight']/$value_temp['in_case_qty'],3);


                    $value_temp['net_weight']  = $value['net_weight'];
                    $value_temp['rought_weight']  = $value['rought_weight'];

                    $value_temp['purchase_unit_price']  = $items_info['purchase_unit_price'];
                    $value_temp['purchase_price_total']  = round($items_info['purchase_unit_price']*$value['in_case_qty'],2);

                    $value_temp['is_drawback'] = isset($value['is_drawback']) ? $is_drawback_list[$value['is_drawback']] : '';
                    $value_temp['enable']  = $value['enable']==1?'有效':'无效';
                    $value_temp['update_time']  = $value['update_time']!='0000-00-00 00:00:00'?$value['update_time']:$value['create_time'];
                    $data_list_temp[] = $value_temp;

                }

            }
            $data_list['box_detail'] = $data_list_temp;

            return ['code'=>TRUE,'data'=>$data_list,'msg'=>'请求成功'];



        } else {
            return ['code'=>false,'data'=>'','msg'=>'数据异常'];


        }




    }


    //获取物流类型
    private function get_logistics_type()
    {
        $result = $this->purchase_db->select('*')->from('logistics_logistics_type')->get()->result_array();

        return empty($result)?null:array_column($result,'type_name','type_code');


    }


    public function get_container_log_list($params)
    {

        $query_builder = $this->purchase_db;
        $query_builder->where('container_sn', $params['container_sn']);
        $result = $query_builder->get($this->container_log)->result_array();

        $type_arr = [1=>'数量变化',2=>'是否作废'];

        if (!empty($result)) {
            foreach ($result as &$val) {
                $val['type'] = $type_arr[$val['type']];


            }

        }
        return $result;
    }

    /**
     * 打印采购单返回数据
     */
    public function get_print_menu($data,$uid){
        $print_menu   = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_box');
        $url          = $print_menu;
        $header = array('Content-Type: application/json');
        $html = getCurlData($url,json_encode($data, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果
        return $html;
    }

    /*
     * 时间过滤
     */
    public function time_filter($time)
    {
        return $time == '0000-00-00 00:00:00'?'':$time;


    }




















}