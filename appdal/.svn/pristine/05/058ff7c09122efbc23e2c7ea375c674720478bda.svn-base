<?php
/**
 * 多货列表
 * User: Dean
 * Date: 2019/01/16 10:00
 */

class Virtual_storage extends MY_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('virtual_storage_model');
    }







    //虚拟单列表

    public function get_storage_list()
    {
        $params=[

            'storage_number' => $this->input->get_post('storage_number'),// 虚拟
            'audit_status'    =>$this->input->get_post('audit_status'),
            'purchase_number'=> $this->input->get_post('purchase_number'),
            'purchase_order_status'=>$this->input->get_post('purchase_order_status'),
            'apply_id'=> $this->input->get_post('apply_id'),
            'create_time_start'=> $this->input->get_post('create_time_start'),
            'create_time_end'=> $this->input->get_post('create_time_end'),
            'audit_id'=> $this->input->get_post('audit_id'),
            'audit_time_start'=> $this->input->get_post('audit_time_start'),
            'audit_time_end'=> $this->input->get_post('audit_time_end'),
            'supplier_code'=>$this->input->get_post('supplier_code'),
            'pai_number'=>$this->input->get_post('pai_number')

        ];

        $page_data=$this->format_page_data();
        $result=$this->virtual_storage_model->get_storage_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);

    }




    //查看审核信息

    public function view_storage_detail()
    {
        $storage_number = $this->input->get_post('storage_number');
        if (empty($storage_number)) {
            $this->error_json('虚拟入库单号不存在');

        }
        $result = $this->virtual_storage_model->view_storage_detail($storage_number);
        $this->success_json($result,$result['paging_data']);



    }





    //批量审核调拨列表
    public function batch_audit_storage_order()
    {
        $audit_result =     $this->input->get_post('audit_result');//审核结果1通过2驳回
        $storage_number =     $this->input->get_post('storage_number');
        $remark =     $this->input->get_post('remark');//审核备注

        $error_list = [];

        if ($audit_result == 2 &&empty($remark)) {
            $this->error_json('驳回必须填备注');

        }

        if (!is_array($storage_number)||empty($storage_number)||empty($audit_result)) {
            $this->error_json('数据格式有误');


        }
        $storage_number = array_unique($storage_number);

        foreach ($storage_number as $number) {
            $result = $this->virtual_storage_model->audit_storage_order($audit_result,$number,$remark);

            if (!$result['code']) {
                $error_list[$number] = $result['msg'];

            }


        }

        $this->success_json($error_list,null,'批量审核完成');



    }


    //日志

    public function view_log()
    {
        $storage_number = $this->input->get_post('storage_number');
        if (empty($storage_number)) {
            $this->error_json('虚拟入库单号不存在');

        }
        $result = $this->virtual_storage_model->view_log($storage_number);
        $this->success_json($result);



    }


















}