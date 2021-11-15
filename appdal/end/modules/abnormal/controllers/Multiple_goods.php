<?php
/**
 * 多货列表
 * User: Dean
 * Date: 2019/01/16 10:00
 */

class Multiple_goods extends MY_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->helper('abnormal');
        $this->load->model('multiple_goods_model');
    }
    /**
     * 获取多货列表数据
     * /abnormal/abnormal_list/get_abnormal_list
     * @author Dean 2019/01/16
     */
    public function multiple_goods_list(){



        $params=[

            'multiple_number' => $this->input->get_post('multiple_number'),// 多货编号
            'status' =>$this->input->get_post('status'),
            'sku' =>$this->input->get_post('sku'),
            'instock_batch' =>$this->input->get_post('instock_batch'),
            'purchase_number' =>$this->input->get_post('purchase_number'),
            'demand_number'=>$this->input->get_post('demand_number'),
            'supplier_code'=>$this->input->get_post('supplier_code'),
            'instock_date_start'=>$this->input->get_post('instock_date_start'),
            'instock_date_end'=>$this->input->get_post('instock_date_end'),
            'groupname'=>$this->input->get_post('groupname'),
            'buyer_id'=>$this->input->get_post('buyer_id'),



        ];

        if (!empty($params['groupname'])) {
            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
                $params['groupdatas'] = $groupdatas;
            }

        }





        $page_data=$this->format_page_data();
        $result=$this->multiple_goods_model->multiple_goods_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);
    }




    //多货退货列表

    public function get_multiple_return_goods(){
        $params=[

            'multiple_number' => $this->input->get_post('multiple_number'),// 多货编号
            'status' =>$this->input->get_post('status'),
            'sku' =>$this->input->get_post('sku'),
            'return_number' => $this->input->get_post('return_number'),// 退货编号
            'instock_batch' =>$this->input->get_post('instock_batch'),
            'create_time_start'=>$this->input->get_post('create_time_start'),
            'create_time_end'=>$this->input->get_post('create_time_end'),
            'apply_id'=>$this->input->get_post('apply_id'),
            'instock_date_start'=>$this->input->get_post('instock_date_start'),
            'instock_date_end'=>$this->input->get_post('instock_date_end'),
            'track_status'=>$this->input->get_post('track_status'),//轨迹状态（多个轨迹状态用逗号分隔）
            'express_no'=>$this->input->get_post('express_no'),//快递单号





        ];


        $page_data=$this->format_page_data();
        $result=$this->multiple_goods_model->get_multiple_return_goods($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);

    }

    //退货信息展示

    public function multiple_return_show()
    {
        $multiple_number = $this->input->get_post('multiple_number');
        if (empty($multiple_number)) {
            $this->error_json('多货编号为空');

        }
        $info = $this->multiple_goods_model->get_basic_multiple_info(0,$multiple_number);
        $info['province_list'] = $this->multiple_goods_model->get_province();
        $info['ReturnFreightPaymentTypeList'] = getReturnFreightPaymentType();
        $info['ReturnFreightPaymentTypeListDefault'] = '3';
        $this->success_json($info);


    }


    //退货信息保存

    public function multiple_return_save()
    {
       $params = [
           'id'      =>$this->input->get_post('id'),
           'return_freight_payment_type'=> $this->input->get_post('return_freight_payment_type'),
           'quantity'=> $this->input->get_post('quantity'),
           'contact_person'=> $this->input->get_post('contact_person'),
           'return_phone'=> $this->input->get_post('return_phone'),
           'return_province'=> $this->input->get_post('return_province'),
           'return_city'=> $this->input->get_post('return_city'),
           'return_area'=> $this->input->get_post('return_area'),
           'return_address'=> $this->input->get_post('return_address'),
           'remark'=> $this->input->get_post('remark'),
           'proof' => $this->input->get_post('proof'),


       ];
        if (empty($params['id'])) {
            $this->error_json('退货信息有误');

        }
        if (empty($params['return_freight_payment_type'])||empty($params['quantity'])||empty($params['contact_person'])||empty($params['return_phone'])||empty($params['return_province'])||empty($params['return_city'])||empty($params['return_area'])||empty($params['return_address'])||empty($params['remark'])||empty($params['proof'])) {
            $this->error_json('必填信息缺失，请检查');


        }
        $result = $this->multiple_goods_model->multiple_return_save($params);

        if($result['code']){
            $this->success_json([],null,'退货成功');
        }else{
            $this->error_json($result['msg']);
        }


    }

    //退货信息展示(单个)

    public function get_return_multiple_info()
    {
        $return_number = $this->input->get_post('return_number');
        if (empty($return_number)) {
            $this->error_json('退货编号不存在');

        }
        $info = $this->multiple_goods_model->get_return_multiple_info($return_number);
        $this->success_json($info);

    }


    //多货调拨列表

    public function get_transfer_multiple_list()
    {
        $params=[

            'multiple_number' => $this->input->get_post('multiple_number'),// 多货编号
            'status' =>$this->input->get_post('status'),
            'transfer_number' =>$this->input->get_post('transfer_number'),
            'audit_status'    =>$this->input->get_post('audit_status'),
            'sku' =>$this->input->get_post('sku'),
            'instock_batch' =>$this->input->get_post('instock_batch'),
            'instock_date_start'=>$this->input->get_post('instock_date_start'),
            'instock_date_end'=>$this->input->get_post('instock_date_end'),
            'purchase_number'=> $this->input->get_post('purchase_number'),
            'purchase_order_status'=>$this->input->get_post('purchase_order_status'),
            'demand_number'=> $this->input->get_post('demand_number'),
            'demand_status'=> $this->input->get_post('demand_status'),
            'apply_id'=> $this->input->get_post('apply_id'),
            'create_time_start'=> $this->input->get_post('create_time_start'),
            'create_time_end'=> $this->input->get_post('create_time_end'),
            'audit_id'=> $this->input->get_post('audit_id'),
            'audit_time_start'=> $this->input->get_post('audit_time_start'),
            'audit_time_end'=> $this->input->get_post('audit_time_end'),



        ];

        $page_data=$this->format_page_data();
        $result=$this->multiple_goods_model->get_transfer_multiple_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);

    }


    public function audit_transfer_show()
    {
         $transfer_number = $this->input->get_post('transfer_number');
         if (empty($transfer_number)) {
             $this->error_json('调拨单号不存在');


         }
         $result = $this->multiple_goods_model->view_transfer_detail($transfer_number);
        $this->success_json($result);



    }

    //查看审核信息

    public function view_transfer_detail()
    {
        $transfer_number = $this->input->get_post('transfer_number');
        if (empty($transfer_number)) {
            $this->error_json('调拨单号不存在');

        }
        $result = $this->multiple_goods_model->view_transfer_detail($transfer_number);
        $this->success_json($result,$result['paging_data']);



    }


    //单个审核调拨列表
    public function audit_transfer_order()
    {
        $audit_result =     $this->input->get_post('audit_result');//审核结果1通过2驳回
        $transfer_number =     $this->input->get_post('transfer_number');
        $remark =     $this->input->get_post('remark');//审核备注

        if ($audit_result == 2 &&empty($remark)) {
            $this->error_json('驳回必须填备注');

        }
        $result = $this->multiple_goods_model->audit_transfer_order($audit_result,$transfer_number,$remark);

        if($result['code']){
            $this->success_json([],null,'审核');
        }else{
            $this->error_json($result['msg']);
        }




    }



    //批量审核调拨列表
    public function batch_audit_transfer_order()
    {
        $audit_result =     $this->input->get_post('audit_result');//审核结果1通过2驳回
        $transfer_number =     $this->input->get_post('transfer_number');
        $remark =     $this->input->get_post('remark');//审核备注

        $error_list = [];

        if ($audit_result == 2 &&empty($remark)) {
            $this->error_json('驳回必须填备注');

        }

        if (!is_array($transfer_number)||empty($transfer_number)) {
            $this->error_json('数据格式有误');


        }
        $transfer_number = array_unique($transfer_number);

        foreach ($transfer_number as $number) {
            $result = $this->multiple_goods_model->audit_transfer_order($audit_result,$number,$remark);

            if (!$result['code']) {
                $error_list[$result['msg']][] = $number;

            }


        }

        $this->success_json($error_list,null,'批量审核完成');



    }


    /**
     * 获取多货列表数据
     * /abnormal/abnormal_list/get_abnormal_list
     * @author Dean 2019/01/16
     */
    public function multiple_list_amount_total(){

        $params=[

            'multiple_number' => $this->input->get_post('multiple_number'),// 多货编号
            'status' =>$this->input->get_post('status'),
            'sku' =>$this->input->get_post('sku'),
            'instock_batch' =>$this->input->get_post('instock_batch'),
            'purchase_number' =>$this->input->get_post('purchase_number'),
            'demand_number'=>$this->input->get_post('demand_number'),
            'supplier_code'=>$this->input->get_post('supplier_code'),
            'instock_date_start'=>$this->input->get_post('instock_date_start'),
            'instock_date_end'=>$this->input->get_post('instock_date_end'),
            'groupname'=>$this->input->get_post('groupname'),
            'buyer_id'=>$this->input->get_post('buyer_id')



        ];

        if (!empty($params['groupname'])) {
            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
                $params['groupdatas'] = $groupdatas;
            }

        }



        $data=$this->multiple_goods_model->multiple_list_amount_total($params);
        $this->success_json($data);
    }







    /**
     * 导出CSV
     *      1、勾选-按照 ID 导出
     *      2、未勾选-按照 查询条件导出
     * @author Dean
     */
    public function multiple_export_csv(){
        set_time_limit(0);
        ini_set('memory_limit','2500M');
        $this->load->helper('export_csv');


            $params=[

                'multiple_number' => $this->input->get_post('multiple_number'),// 多货编号
                'status' =>$this->input->get_post('status'),
                'sku' =>$this->input->get_post('sku'),
                'instock_batch' =>$this->input->get_post('instock_batch'),
                'purchase_number' =>$this->input->get_post('purchase_number'),
                'demand_number'=>$this->input->get_post('demand_number'),
                'supplier_code'=>$this->input->get_post('supplier_code'),
                'instock_date_start'=>$this->input->get_post('instock_date_start'),
                'instock_date_end'=>$this->input->get_post('instock_date_end'),
                'groupname'=>$this->input->get_post('groupname'),
                'buyer_id'=>$this->input->get_post('buyer_id'),
                'ids'     =>$this->input->get_post('ids')


            ];

            if (!empty($params['groupname'])) {
                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                    $params['groupdatas'] = $groupdatas;
                }

            }

            $list = $this->multiple_goods_model->multiple_goods_list($params,null,null,1,true);
            $data_list = $list['data_list']['value'];


        $columns = [
            '多货编号',
            '多货状态',
            'SKU',
            '产品线',
            '未税单价',
            '产品名称',
            '入库批次',
            '入库时间',
            '多货数量',
            '已转调拨数量',
            '已转退货数量',
            '剩余多货数量',
            '原采购单号',
            '原备货单号',
            '原备货单业务线',
            '付款状态',
            '采购主体',
            '供应商名称',
            '结算方式',
            '支付方式',
            '采购员',
            '采购仓库'

        ];

        $data_list_temp = [];
        if($data_list){
            foreach($data_list as $v_value){

                $v_value_tmp['multiple_number']       = $v_value['multiple_number'];
                $v_value_tmp['status']     = $v_value['status'];
                $v_value_tmp['sku']    = $v_value['sku'];
                $v_value_tmp['line_name']       = $v_value['line_name'];
                $v_value_tmp['price']      =  $v_value['price'];
                $v_value_tmp['product_name']          = $v_value['product_name'];
                $v_value_tmp['instock_batch']      = $v_value['instock_batch'];
                $v_value_tmp['instock_date']  = $v_value['instock_date'];
                $v_value_tmp['total_num'] = $v_value['total_num'];
                $v_value_tmp['transfer_num']  = $v_value['transfer_num'];
                $v_value_tmp['return_num'] = $v_value['return_num'];
                $v_value_tmp['remain_num']        = $v_value['remain_num'];
                $v_value_tmp['purchase_number']        = $v_value['purchase_number'];
                $v_value_tmp['demand_number']      = $v_value['demand_number'];
                $v_value_tmp['business_line']      = $v_value['business_line'];
                $v_value_tmp['pay_status']       = $v_value['pay_status'];
                $v_value_tmp['purchase_name']         = $v_value['purchase_name'];
                $v_value_tmp['supplier_name']      = $v_value['supplier_name'];
                $v_value_tmp['settlement']        = $v_value['settlement'];
                $v_value_tmp['pay_method']        = $v_value['pay_method'];
                $v_value_tmp['buyer_name']            = $v_value['buyer_name'];
                $v_value_tmp['warehouse_name']         = $v_value['warehouse_name'];
                $data_list_temp[] = $v_value_tmp;
            }
        }

        unset($data_list);
        $data = [
            'key' => $columns,
            'value' => $data_list_temp,
        ];
        $this->success_json($data);
    }
















}