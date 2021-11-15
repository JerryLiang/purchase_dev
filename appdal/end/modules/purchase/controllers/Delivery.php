<?php
/**
 * Created by PhpStorm.
 * 权均交期控制器
 * User: Jaden
 * Date: 2019/01/17 
 */

class Delivery extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('delivery_model','delivery');
        $this->load->model('purchase_user_model','product_user',false,'user');
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->model('product_model','product',false,'product');
        $this->load->model('warehouse_model','warehouse_model',false,'warehouse');
    }

    /**
     * 权均交期列表
     * /purchase/delivery/delivery_list
     * @author Jaden 2019-1-17
    */
    public function delivery_list(){

        $this->load->helper('status_product');
        $params = [
            'sku'               => $this->input->get_post('sku'), // SKU
            'supplier_code'     => $this->input->get_post('supplier_code'), // 供应商

            'product_line_id'   => $this->input->get_post('product_line_id'), // 产品线
            'product_status'    => $this->input->get_post('product_status'), // 产品状态
            'is_purch'          => $this->input->get_post('is_purch'), // 是否代采
            'is_customized'     => $this->input->get_post('is_customized'), // 是否定制
            'business_line'     => $this->input->get_post('business_line'), // 业务线
            'start'             => $this->input->get_post('start'), // 权均交期开始时间
            'end'               => $this->input->get_post('end'), // 权均交期结束时间
            'warehouse_code'    => $this->input->get_post('warehouse_code'), // 仓库CODE
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $field ='ware.purchase_type_id,p.product_status,p.is_customized,p.is_purchasing,d.id,d.sku,d.warehouse_code,d.avg_delivery_time,p.product_status,p.product_line_id,p.supplier_name,d.statistics_date';
        $orders_info = $this->delivery->get_delivery_list($params, $offset, $limit,$field);
        $orders_info['key'] = array('sku','产品状态','产品线','供应商','权均交期(天)','采购仓库');
        $product_line_list = $this->product_line->get_product_line_list(0);
        $drop_down_box['product_line_id'] =array_column($product_line_list, 'linelist_cn_name','product_line_id');
        $data_list = $orders_info['value'];
        $warehouseCode= $this->delivery->get_warehouseCode();
        $drop_down_box['warehouse_name'] = [];
        if(!empty($warehouseCode)){
            $ware = [];
            foreach($warehouseCode as $wareKey=>$wareValue){
                $ware[$wareValue['warehouse_code']] = $wareValue['warehouse_name'];
            }

            $drop_down_box['warehouse_name']  = $ware;
        }
        $drop_down_box['customized'] = [1=>'是',2=>'否'];
        $drop_down_box['purch'] = [1=>'否',2=>'是'];
        $drop_down_box['business_line'] = [1=>'国内',2=>'海外',3=>'FBA'];
        $drop_down_box['product_status'] = getProductStatus();

        $warehouse_code_arr = array_column($data_list, 'warehouse_code');
        $warehouse_list = $this->warehouse_model->get_code2name_list($warehouse_code_arr);

        $role_name=get_user_role();//当前登录角色
        $data_list = ShieldingData($data_list,['supplier_name','supplier_code'],$role_name,NULL);
        foreach ($data_list as $key => $value) {
            $linedata = $this->product_line_model->get_all_parent_category($value['product_line_id']);
            $product_line_name = '';
            if(isset($linedata[0])){
                $product_line_name = $linedata[0]['product_line_name'];
            }

            $orders_info['value'][$key]['product_status'] = !empty($value['product_status'])?getProductStatus($value['product_status']):'';
            $orders_info['value'][$key]['product_line_id'] = $product_line_name;
            $orders_info['value'][$key]['delivery_days'] = $value['avg_delivery_time'];
            $orders_info['value'][$key]['warehouse_code'] = !empty($value['warehouse_code'])?$warehouse_list[$value['warehouse_code']]:'';
            $orders_info['value'][$key]['warehouse_name'] = $value['warehouse_code'];

            if($value['is_customized'] == 1){

                $orders_info['value'][$key]['is_customized_ch'] = '是';
            }else{
                $orders_info['value'][$key]['is_customized_ch'] = '否';
            }

            if($value['is_purchasing'] == 1){

                $orders_info['value'][$key]['is_purch_ch'] = '否';
            }else{
                $orders_info['value'][$key]['is_purch_ch'] = '是';
            }

            if( $value['purchase_type_id'] == 1){

                $orders_info['value'][$key]['business_line_chs'] = '国内';
            }

            if( $value['purchase_type_id'] == 2){

                $orders_info['value'][$key]['business_line_chs'] = '海外';
            }

            if( $value['purchase_type_id'] == 3){

                $orders_info['value'][$key]['business_line_chs'] = 'FBA';
            }

            //getProductStatus
            $orders_info['value'][$key]['product_status'] = getProductStatus($value['product_status']);
            //需求业务线(1.国内,2.海外,3.FBA)
        }
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        $orders_info['drop_down_box'] = $drop_down_box;

        $this->success_json($orders_info);
    }

    /**
     * 权均交期列表导出
     * /purchase/delivery/delivery_export
     * @author Jaden 2019-1-17
    */
    public function delivery_export(){
        set_time_limit(0);
        $this->load->model('system/Data_control_config_model');
        $this->load->helper('status_product');
        $ids = $this->input->get_post('ids');
        if(!empty($ids)){
            $params['ids']   = $ids;
        }else{
            $params = [
                'sku' => $this->input->get_post('sku'), // SKU
                'supplier_code' => $this->input->get_post('supplier_code'), // 供应商
                'product_line_id' => $this->input->get_post('product_line_id'), // 产品线
                'product_status'    => $this->input->get_post('product_status'), // 产品状态
                'is_purch'          => $this->input->get_post('is_purch'), // 是否代采
                'is_customized'     => $this->input->get_post('is_customized'), // 是否定制
                'business_line'     => $this->input->get_post('business_line'), // 业务线
                'start'             => $this->input->get_post('start'), // 权均交期开始时间
                'end'               => $this->input->get_post('end'), // 权均交期结束时间
                'warehouse_code'    => $this->input->get_post('warehouse_code'), // 仓库CODE
            ];
        }
        $field ='ware.purchase_type_id,p.product_status,p.is_customized,p.is_purchasing,d.id,d.sku,d.warehouse_code,d.avg_delivery_time,p.product_status,p.product_line_id,p.supplier_name';

        $total = $this->delivery->get_delivery_total($params, '', '',$field,true);
        $result = $this->Data_control_config_model->insertDownData($params, 'DELIVERY', 'sku权均交期数据导出', getActiveUserName(), 'csv', $total);
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }
        die();
        $orders_info = $this->delivery->get_delivery_list($params, '', '',$field,true);
        $delivery_list = $orders_info['value'];

        $tax_list_tmp = [];
        $warehouse_code_arr = array_column($delivery_list, 'warehouse_code');
        $warehouse_list = $this->warehouse_model->get_code2name_list($warehouse_code_arr);
        foreach ($delivery_list as $key => $value) {

            $linedata = $this->product_line_model->get_all_parent_category($value['product_line_id']);
            $product_line_name = '';
            if(isset($linedata[0])){
                $product_line_name = $linedata[0]['product_line_name'];
            }


            $v_value_tmp = [];
            $v_value_tmp['sku'] = " ".$value['sku'];
            $v_value_tmp['product_status'] = !empty($value['product_status'])?getProductStatus($value['product_status']):''; 
            $v_value_tmp['product_line_id'] = $product_line_name;
            $v_value_tmp['supplier_name'] = $value['supplier_name'];
            $v_value_tmp['delivery_days'] = $value['avg_delivery_time'];
            $v_value_tmp['warehouse_code'] = !empty($value['warehouse_code'])?$warehouse_list[$value['warehouse_code']]:'';
            $EveryMonthData = $this->delivery->EveryMonth($value['sku'],$value['warehouse_code']);
            $v_value_tmp['monthData'] = $EveryMonthData;
            $tax_list_tmp[] = $v_value_tmp;
        }
        $this->success_json($tax_list_tmp);

    }

    /**
     * 获取权限交期的日志数据
     * @params :无
     * @MTHODS :GET
     * @AUTHOR:LUXU
     * @time: 2020/6/15
     **/
    public function getDeliveryLogs(){

        try{

            $skus = $this->input->get_post('sku');
            if(empty($skus) || NULL == $skus){

                throw new Exception("请传入SKU");
            }
            $pages = $this->input->get_post('offset');
            $limit = $this->input->get_post('limit');
            $warehouse_code = $this->input->get_post('warehouse_name');
            if(NULL == $pages) {
                $pages = 1;
            }
            if(NULL == $limit) {
                $limit =100;
            }
            $offset = ($pages - 1) * $limit;
            $result = $this->delivery->getDeliveryLogs($skus,$offset,$limit,$warehouse_code);
            if( isset($result['data']) && !empty($result['data'])){

                foreach($result['data'] as $key=>$value){

                    if($value['is_effect'] == 2){

                        $result['data'][$key]['is_effect_ch'] = "有效";
                    }else{
                        $result['data'][$key]['is_effect_ch'] = "无效";
                    }
                }
            }
            $this->success_json($result);
        }catch ( Exception $exp ){

            $this->error_info($exp->getMessage());
        }
    }
    

}