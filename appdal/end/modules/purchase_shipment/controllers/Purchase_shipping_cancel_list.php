<?php

/**
 * 发运作废管理
 * @time:2020/4/27
 * @author:Dean
 **/
class Purchase_shipping_cancel_list extends MY_Controller{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_shipping_cancel_list_model');
    }


    //系统内取消，推送计划系统取消列表
    public function get_cancel_list()
    {
        $params=[
            'sku' => $this->input->get_post('sku'),
            'pur_number' => $this->input->get_post('pur_number'),//采购单号
            'cancel_number' => $this->input->get_post('cancel_number'),//申请编号
            'apply_person' => $this->input->get_post('apply_person'),//申请人
            'audit_status' => $this->input->get_post('audit_status'),
            'apply_time_start' => $this->input->get_post('apply_time_start'),//申请时间开始
            'apply_time_end' => $this->input->get_post('apply_time_end'),//申请时间截止
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'shipment_type' =>$this->input->get_post('shipment_type'),// 发运类型
        ];

        $page_data=$this->format_page_data();
        $result=$this->purchase_shipping_cancel_list_model->get_cancel_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
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
    public function get_export_cancel_list(){
        $id=$this->input->get_post('id');
        if(!empty($id)){
            $params['id']=explode(',', $id);
        }else{
            $params=[
                'sku' => $this->input->get_post('sku'),
                'pur_number' => $this->input->get_post('pur_number'),//采购单号
                'cancel_number' => $this->input->get_post('cancel_number'),//取消编码
                'apply_person' => $this->input->get_post('apply_person'),//申请人
                'audit_status' => $this->input->get_post('audit_status'),
                'apply_time_start' => $this->input->get_post('apply_time_start'),//申请时间开始
                'apply_time_end' => $this->input->get_post('apply_time_end'),//申请时间截止
                'demand_number' => $this->input->get_post('demand_number'),// 备货单号
                'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
                'shipment_type' =>$this->input->get_post('shipment_type')// 发运类型

            ];
        }
        $result=$this->purchase_shipping_cancel_list_model->get_cancel_list($params);
        if(!empty($result['data_list']['value'])){
            $this->success_json($result['data_list']['value']);
        }else{
            $this->error_json('没有获取到数据');
        }
    }















}