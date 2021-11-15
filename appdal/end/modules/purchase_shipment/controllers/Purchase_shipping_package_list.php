<?php

/**
 * 装箱装柜-包装管理
 * @time:2020/4/27
 * @author:Dean
 **/
class Purchase_shipping_package_list extends MY_Controller{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_shipping_package_list_model');
    }
    //获取柜子列表
    public function get_cabinet_list()
    {

        $params=[

            'demand_number' => $this->input->get_post('demand_number'),// 备货单号,可以搜出原备货单号，也可以搜出新备货单号
            'purchase_number'=> $this->input->get_post('purchase_number'),
            'supplier_code'  =>  $this->input->get_post('supplier_code'),
            'shipment_type'  =>  $this->input->get_post('shipment_type'),
            'compact_number' =>   $this->input->get_post('compact_number'),
            'purchase_order_status'=> $this->input->get_post('purchase_order_status'),
            'demand_status'=> $this->input->get_post('demand_status'),
            'enable' => $this->input->get_post('enable'),
            'virtual_container_sn' => $this->input->get_post('virtual_container_sn'),
            'is_drawback' =>$this->input->get_post('is_drawback'),
            'is_package_box'=>$this->input->get_post('is_package_box'),
            'warehouse_code'=>$this->input->get_post('warehouse_code'),
            'buyer_id'=>$this->input->get_post('buyer_id'),
            'logistics_type'=>$this->input->get_post('logistics_type'),
            'destination_warehouse'=>$this->input->get_post('destination_warehouse'),
            'container_sn'=>$this->input->get_post('container_sn'),
            'estimate_date_start'=> $this->input->get_post('estimate_date_start'),
            'estimate_date_end'=> $this->input->get_post('estimate_date_end'),
            'create_time_start'=> $this->input->get_post('create_time_start'),
            'create_time_end'=> $this->input->get_post('create_time_end'),
            'update_time_start'=> $this->input->get_post('update_time_start'),
            'update_time_end'=> $this->input->get_post('update_time_end'),
            'cabinet_type_id'=> $this->input->get_post('cabinet_type_id'),//柜型
            'document_status' => $this->input->get_post('document_status')



        ];

        $page_data=$this->format_page_data();
        $result=$this->purchase_shipping_package_list_model->get_cabinet_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);
    }

    public function format_page_data(){
        $page = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;
        return [
            'offset' => $offset,
            'limit' => $limit,
            'page' => $page
        ];
    }

    /**
     * 获取导出数据
     * /abnormal/report_loss/get_export_report_loss_data
     * @author Jaxton 2019/01/31
     */
    public function get_export_cabinet_list(){
        $id=$this->input->get_post('id');
        if(!empty($id)){
            $params['id']=explode(',', $id);
        }else{
            $params=[
                'demand_number' => $this->input->get_post('demand_number'),// 备货单号,可以搜出原备货单号，也可以搜出新备货单号
                'purchase_number'=> $this->input->get_post('purchase_number'),
                'supplier_code'  =>  $this->input->get_post('supplier_code'),
                'shipment_type'  =>  $this->input->get_post('shipment_type'),
                'compact_number' =>   $this->input->get_post('compact_number'),
                'purchase_order_status'=> $this->input->get_post('purchase_order_status'),
                'demand_status'=> $this->input->get_post('demand_status'),
                'enable' => $this->input->get_post('enable'),
                'virtual_container_sn' => $this->input->get_post('virtual_container_sn'),
                'is_drawback' =>$this->input->get_post('is_drawback'),
                'is_package_box'=>$this->input->get_post('is_package_box'),
                'warehouse_code'=>$this->input->get_post('warehouse_code'),
                'buyer_id'=>$this->input->get_post('buyer_id'),
                'logistics_type'=>$this->input->get_post('logistics_type'),
                'destination_warehouse'=>$this->input->get_post('destination_warehouse'),
                'container_sn'=>$this->input->get_post('container_sn'),
                'estimate_date_start'=> $this->input->get_post('estimate_date_start'),
                'estimate_date_end'=> $this->input->get_post('estimate_date_end'),
                'create_time_start'=> $this->input->get_post('create_time_start'),
                'create_time_end'=> $this->input->get_post('create_time_end'),
                'update_time_start'=> $this->input->get_post('update_time_start'),
                'update_time_end'=> $this->input->get_post('update_time_end'),
                'cabinet_type_id'=> $this->input->get_post('cabinet_type_id')//柜型

            ];
        }
        $result=$this->purchase_shipping_package_list_model->get_cabinet_list($params);
        if(!empty($result['data_list']['value'])){
            $this->success_json($result['data_list']['value']);
        }else{
            $this->error_json('没有获取到数据');
        }
    }


    /**
     * 需求单导入
     * @author Jolon
     */
    public function import_package_list(){

        $data = file_get_contents('php://input');
        $data = json_decode($data,true);

        $data = $data['data'];


        if($data){
            $result = $this->purchase_shipping_package_list_model->import_package_list($data);
            if($result['code']){
                $this->success_json([],null,$result['message']);
            }else{
                $this->error_data_json($result['data'],$result['message']);
            }
        }else{
            $this->error_json('数据缺失');
        }
    }

    //获取箱子列表
    public function get_box_list()
    {
        $params=[
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号,可以搜出原备货单号，也可以搜出新备货单号
            'purchase_number'=> $this->input->get_post('purchase_number'),//
            'supplier_code'  =>  $this->input->get_post('supplier_code'),//
            'purchase_order_status'=> $this->input->get_post('purchase_order_status'),//
            'demand_status'=> $this->input->get_post('demand_status'),//
            'virtual_container_sn' => $this->input->get_post('virtual_container_sn'),//虚拟柜号
            'is_drawback' =>$this->input->get_post('is_drawback'),//
            'warehouse_code'=>$this->input->get_post('warehouse_code'),// warehouse_code
            'destination_warehouse'=>$this->input->get_post('destination_warehouse'),//
            'container_sn'=>$this->input->get_post('container_sn'),//
            'box_sn'=>$this->input->get_post('box_sn'),//
            'estimate_date_start'=> $this->input->get_post('estimate_date_start'),//
            'estimate_date_end'=> $this->input->get_post('estimate_date_end'),//
            'create_time_start'=> $this->input->get_post('create_time_start'),//
            'create_time_end'=> $this->input->get_post('create_time_end'),//
            'cabinet_type_id'=> $this->input->get_post('cabinet_type_id'),//柜型
            'create_user'    => $this->input->get_post('create_user'),//*/
            'check_time_start'     => $this->input->get_post('check_time_start'),
            'check_time_end'     => $this->input->get_post('check_time_end'),


        ];
        $page_data=$this->format_page_data();
        $result=$this->purchase_shipping_package_list_model->get_box_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);
    }


    /**
     * 获取导出数据
     * /abnormal/report_loss/get_export_report_loss_data
     * @author Jaxton 2019/01/31
     */
    public function get_export_box_list(){
        $id=$this->input->get_post('id');
        if(!empty($id)){
            $params['id']=explode(',', $id);
        }else{
            $params=[
                'demand_number' => $this->input->get_post('demand_number'),// 备货单号,可以搜出原备货单号，也可以搜出新备货单号
                'purchase_number'=> $this->input->get_post('purchase_number'),//
                'supplier_code'  =>  $this->input->get_post('supplier_code'),//
                'purchase_order_status'=> $this->input->get_post('purchase_order_status'),//
                'demand_status'=> $this->input->get_post('demand_status'),//
                'virtual_container_sn' => $this->input->get_post('virtual_container_sn'),//虚拟柜号
                'is_drawback' =>$this->input->get_post('is_drawback'),//
                'warehouse_code'=>$this->input->get_post('warehouse_code'),//
                'destination_warehouse'=>$this->input->get_post('destination_warehouse'),//
                'container_sn'=>$this->input->get_post('container_sn'),//
                'box_sn'=>$this->input->get_post('box_sn'),//
                'estimate_date_start'=> $this->input->get_post('estimate_date_start'),//
                'estimate_date_end'=> $this->input->get_post('estimate_date_end'),//
                'create_time_start'=> $this->input->get_post('create_time_start'),//
                'create_time_end'=> $this->input->get_post('create_time_end'),//
                'cabinet_type_id'=> $this->input->get_post('cabinet_type_id'),//柜型
                'create_user'    => $this->input->get_post('create_user'),//*/

            ];
        }
        $result=$this->purchase_shipping_package_list_model->get_box_list($params);
        if(!empty($result['data_list']['value'])){
            $this->success_json($result['data_list']['value']);
        }else{
            $this->error_json('没有获取到数据');
        }
    }


    //获取装箱明细
    public function get_package_box_list()
    {

        $params = array(


            "container_sn" => $this->input->get_post("container_sn"),
        );

        if (empty($params['container_sn'])) {
            $this->error_json('柜号缺失');
        }

        $result = $this->purchase_shipping_package_list_model->get_package_box_list($params);
        $this->success_json($result);

    }


    /**
     * 打印装箱明细
     * @author harvin 2019-1-10
     * http://www.caigou.com/purchase/purchase_order/printing_purchase_order
     * * */
    public function print_package_box_detail()
    {
        $ids = $this->input->post_get('ids'); //勾选数据
        if (empty($ids)) {
            $this->error_json('请勾选数据');
        }
        $ids = explode(',', $ids);
        if (!is_array($ids)) {
            $this->error_json('请求参数格式错误');
        }


        //查询采购单
        $reslut = $this->purchase_shipping_package_list_model->print_package_box_detail($ids);
        if ($reslut['code']) {
            $this->success_json($reslut['data'], null, $reslut['msg']);
        } else {
            $this->error_json($reslut['msg']);
        }
    }

    //获取ID日志明细
    public function get_container_log_list()
    {

        $params = array(

            "container_sn" => $this->input->get_post("container_sn"),
        );

        if (empty($params['container_sn'])) {
            $this->error_json('柜号缺失');
        }

        $result = $this->purchase_shipping_package_list_model->get_container_log_list($params);
        $this->success_json($result);

    }


    /**
     * 返回打印装箱明细
     * @author Dean
     * **/
    public function print_menu()
    {

        $ids = $this->input->get_post('ids');
        $uid = $this->input->get_post('uid');
        $data = [];
        if (empty($ids) || empty($uid)) {
            $this->error_json('缺少参数');
        }
        //获取前端地址html
        $result = $this->purchase_shipping_package_list_model->print_package_box_detail($ids);

        if ($result['code']) {
            $data = $result['data'];
        } else {
            $this->error_json($result['msg']);
        }

        $html = $this->purchase_shipping_package_list_model->get_print_menu($data, $uid);
        $this->success_json([$html], '成功');
    }
















}