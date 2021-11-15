<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 订单操作控制器
 * User: Jolon
 * Date: 2019/12/20 10:00
 */
class Purchase_label_api extends MY_API_Controller {

    public function __construct(){
        parent::__construct();

        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_label_model');
        $this->load->model('purchase/purchase_order_items_model');
        $this->load->model('purchase/purchase_suggest_model');


    }

    //接收来自仓库系统的po+sku，更改目的仓
    public function receive_warehouse_change()
    {
        $change_data = file_get_contents('php://input'); //接受蓝灵回调数据

        $data = json_decode($change_data,true);

        if (empty($data)) exit('empty data');

       // $error_list = array();

        $send_data = array();

        foreach ($data as $info) {
            //查询是否存在此信息
            $label_info = $this->purchase_label_model->get_label_item($info['purchase_number'],$info['sku']);
            if (!empty($label_info)) {

                $updateData = ['warehouse_is_change'=>1,'new_des_warehouse'=>$info['warehouse_code'],'logistics_change_time'=>$info['modify_time']];
                $this->purchase_label_model->purchase_db->update('purchase_label_info', $updateData,['id' => $label_info['id']]);
                //写入目的仓变更日志
            } /*else {
                $note = $info['purchase_number'].'_'.$info['sku'];
                $error_list[$note] = '采购单号+sku不存在';


            }*/

        }
        /*if (count($error_list)>0) {
            $send_data['error_list'] = $error_list;

        }*/
        $send_data['msg']='接收成功';
        $send_data['status'] = 1;

        echo json_encode($send_data);


    }

    //检索每小时自动更新物流属性的sku，

    public function update_sku_logistics()
    {
        echo 'start process';
        //一个小时前数据
        $before_time  = date('Y-m-d H:i:s',strtotime('-1 hour'));
        $now_time = date('Y-m-d H:i:s');
        $change_sku = $this->purchase_label_model->purchase_db->select(' sku,modify_time')->where('modify_time>=',$before_time)->where('modify_time<=',$now_time)->get('prod_logistics_audit_attr')->result_array();
        $skus_arr = array_column($change_sku,'modify_time','sku');
        $sku_list  = array_keys($skus_arr);

        //要更新的备货单
        if (!empty($sku_list)) {
            $order_list = $this->purchase_label_model->purchase_db->select('*')->where_in('sku',$sku_list)->where('label!=','')->where('barcode!=','')->get('purchase_label_info ')->result_array();

            if (is_array($order_list)&&count($order_list)>0) {
                foreach ($order_list as $order_info) {
                    $updateData = ['logistics_change_time'=>$skus_arr[$order_info['sku']],'logistics_is_change'=>1];
                    $this->purchase_label_model->purchase_db->update('purchase_label_info', $updateData,['id' => $order_info['id']]);

                }
            }

        }

        echo 'success done';




    }

    //每日跑定时任务

    public function receive_update_label_by_day()
    {
        echo 'start process';
        //一个小时前数据
        $before_time  = date('Y-m-d',strtotime('-1 day')).' 00:00:00';
        $now_time = date('Y-m-d',strtotime('-1 day')).' 23:59:59';
        $order_list = $this->purchase_label_model->purchase_db->select('*')->where('logistics_change_time>=',$before_time)->where('logistics_change_time<=',$now_time)->get('purchase_label_info')->result_array();



        if (count($order_list)>0) {
            $offset = 0;
            $query = array_slice($order_list, 0,20);
            while(!empty($query)){
                $this->send_wms_label($query);
                $offset +=20;
                $query = array_slice($order_list, $offset,20);

            }


        }
        echo 'done success';

    }


    /*
     * 批量推送wms标签
     */

    public function send_wms_label($order_list)
    {


        $purchase_arr = array();
        $supplier_code_arr =array();
        $order_list_arr = array();
        //推送门户系统数据
        $send_provier_arr = array();
        foreach ($order_list as $demand_info) {

            $purchase_arr[$demand_info['purchase_number']][] =$demand_info;
            $supplier_code_arr[$demand_info['purchase_number']] = $demand_info['supplier_code'];
            $no_sku = $demand_info['purchase_number'].'-'.$demand_info['sku'];
            $order_list_arr[$no_sku]=$demand_info;
        }


        if (!empty($purchase_arr)) {
            foreach($purchase_arr as $purchase_no=>$demand_info){

                $po = $purchase_no;//单号
                $supplier_code = $supplier_code_arr[$po];
                //请求翻译接口
                $trans_url = SMC_JAVA_API_URL.'/procurement/purSupplier/getSupplierContactByPurchaseNumber';

                $trans_data['purchaseNumber'] = $po;
                $trans_data['sku'] = $demand_info[0]['sku'];

                $header = array('Content-Type: application/json');
                $access_taken = getOASystemAccessToken();
                $trans_url = $trans_url . "?access_token=" . $access_taken;
                $trans_res = getCurlData($trans_url,json_encode($trans_data, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果

                $trans_res = json_decode($trans_res,true);


                if ($trans_res['code']!=200) continue;

                $supplier_info =  $this->purchase_order_model->purchase_db
                    ->select('*')
                    ->where('supplier_code',$supplier_code)
                    ->get('supplier')
                    ->row_array();

                if (empty($supplier_info)) continue;
                foreach ($demand_info as $demand) {
                    //拼接请求信息
                    //如果更新时间大于等于物流属性更新时间

                    if (strtotime($demand['update_time'])>=strtotime($demand['logistics_change_time'])) {
                        continue;
                    }

                    $order_item_info = $this->purchase_order_items_model->get_item($demand['purchase_number'], $demand['sku'],true);//采购数量
                    $num = $order_item_info['confirm_amount'];
                    $send_data_temp = array('purchase_order_no'=>$po,'sku'=>$demand['sku'],'num'=>$num,'en_provider_name'=>$trans_res['data']['supplierName'],'en_provider_address'=>$trans_res['data']['contactAddress'],'provider_phone'=>$trans_res['data']['contactNumber'],'cn_provider_name'=>$supplier_info['supplier_name'],'cn_provider_address'=>!empty($supplier_info['register_address']?$supplier_info['register_address']:$supplier_info['ship_address']));
                    $send_data[] = $send_data_temp;

                }

            }



           // $wms_url = WMS_DOMAIN.'/Api/Transit/Index/createTransitPdfLabel';//测试时候替换
            $wms_url= 'http://dp.yibai-it.com:33335/Api/Transit/Index/createTransitPdfLabel';
            $send_data = array('data'=>json_encode($send_data));
            $send_res = getCurlData($wms_url,$send_data);//翻译结果
            $send_res = json_decode($send_res,true);


               if (!empty($send_res)&&$send_res['status'] == 1) {//推送成功

                    if (is_array($send_res['success_list'])&&count($send_res['success_list'])>0) {
                        foreach($send_res['success_list'] as $success){
                            $update_data = array();
                            $su_item = $order_list_arr[$success['purchase_order_no'].'-'.$success['sku']];
                            if ($su_item){//更新物流标签信息

                                   //如果没有下载需要推送门户系统
                                if (($su_item['purchase_label_down_time']=='0000-00-00 00:00:00')&&($su_item['supplier_down_time']=='0000-00-00 00:00:00'))
                                {
                                    $send_provier_arr[] = $success;

                                }

                                         $update_data['error_mes'] = '';
                                         $update_data['barcode'] = $success['barcode_url'];
                                         $update_data['label'] = $success['pdf_url'];

                                         $update_data['label_is_dispose'] = 2;
                                         $update_data['barcode_is_dispose'] = 2;
                                         $update_data['update_time'] = date('Y-m-d H:i:s'); ;


                                if (!empty($success['pdf_url'])) {

                                            $update_data['label_is_update'] = 1;
                                            $updated_demand_number[] = $su_item['demand_number'];

                                    }
                                    if (!empty($success['barcode_url'])) {
                                            $update_data['barcode_is_update'] = 1;
                                            $updated_demand_number[] = $su_item['demand_number'];

                                    }


                                if (!empty($update_data)){
                                    $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                                        , array('purchase_number'=>$su_item['purchase_number'],'sku'=>$su_item['sku']));
                                }


                            }

                        }

                    }

                }
               if (!empty($send_provier_arr)) {
                   $this->send_provider_label_info($send_provier_arr);

               }


        }




    }

    //将标签更新信息推送门户系统
    public function send_provider_label_info($data)
    {

        foreach ($data as $demand_info ) {

                $send_arr = array();
                $send_data = array('purchaseOrderNo'=>$demand_info['purchase_order_no'],'sku'=>$demand_info['sku'],'barcodePdf'=>$demand_info['barcode_url'],'labelPdf'=>$demand_info['pdf_url'],'is_sole'=>$demand_info['is_unique']);
                $send_arr[] = $send_data;

        }
        $url = SMC_JAVA_API_URL.'/provider/purPush/pushPorivderPostCode';
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;

        $send_res = getCurlData($url, json_encode($send_arr, JSON_UNESCAPED_UNICODE), 'post', $header);
        $send_res = json_decode($send_res,true);

        if (!empty($send_res)&&$send_res['code'] == 200) {
            $update_data = null;
            $update_data['label_is_dispose'] = 1;
            $update_data['barcode_is_dispose'] = 1;
            foreach ($data as $item_info) {
                $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                    , ['purchase_number' =>$item_info['purchase_order_no'],'sku'=>$item_info['sku']]);

            }


        }

    }

    //根据时间定期去获取要获得合并标签的数据
    public function getCombineData()
    {

        set_time_limit(0);
        echo 'start process';
        //一个小时前数据

        $before_time=$this->input->get_post('before_time');
        $id = $this->input->get_post('id');
		$is_test = $this->input->get_post('is_test');
        if (empty($before_time)) {
            $before_time  = date('Y-m-d',strtotime('-1 day')).' 00:00:00';

        }

        if (!empty($id)) {
            $order_list = $this->purchase_label_model->purchase_db->select('*')->where('id',$id)->get('purchase_label_info')->result_array();

        } else {

            $order_list = $this->purchase_label_model->purchase_db->select('*')->where('create_time>=',$before_time)->get('purchase_label_info')->result_array();

        }


        if (count($order_list)>0) {
            $offset = 0;
            $query = array_slice($order_list, 0,20);
            while(!empty($query)){
                $this->send_wms_combine_label($query,$is_test);
                $offset +=20;
                $query = array_slice($order_list, $offset,20);

            }

        }
        echo 'done success';

    }


    public function send_wms_combine_label($order_list,$is_test=null)
    {



        $purchase_arr = array();
        $supplier_code_arr =array();
        $order_list_arr = array();
        $send_data =array();
        //推送门户系统数据
        foreach ($order_list as $demand_info) {

            $purchase_arr[$demand_info['purchase_number']][] =$demand_info;
            $supplier_code_arr[$demand_info['purchase_number']] = $demand_info['supplier_code'];
            $no_sku = $demand_info['purchase_number'].'-'.$demand_info['sku'];
            $order_list_arr[$no_sku]=$demand_info;
        }





        if (!empty($purchase_arr)) {
            foreach($purchase_arr as $purchase_no=>$demand_info){


                $po = $purchase_no;//单号
                $supplier_code = $supplier_code_arr[$po];
                //请求翻译接口
               // $trans_url = SMC_JAVA_API_URL.'/procurement/purSupplier/getSupplierContactByPurchaseNumber';
                $trans_url ='http://rest.java.yibainetwork.com'.'/procurement/purSupplier/getSupplierContactByPurchaseNumber';

                $trans_data['purchaseNumber'] = $po;
                $trans_data['sku'] = $demand_info[0]['sku'];

                $header = array('Content-Type: application/json');
                $access_taken = getOASystemAccessToken();
                $trans_url = $trans_url . "?access_token=" . $access_taken;
                $trans_res = getCurlData($trans_url,json_encode($trans_data, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果

                $trans_res = json_decode($trans_res,true);
				if ($is_test) print_R($trans_res);

                if ($trans_res['code']!=200) continue;

                $supplier_info =  $this->purchase_order_model->purchase_db
                    ->select('*')
                    ->where('supplier_code',$supplier_code)
                    ->get('supplier')
                    ->row_array();

                if (empty($supplier_info)) continue;





                foreach ($demand_info as $demand) {

                    $order_item_info = $this->purchase_order_items_model->get_item($demand['purchase_number'], $demand['sku'],true);//采购数量
                    $num = $order_item_info['confirm_amount'];


                    $suggest_info = $this->purchase_suggest_model->get_one(0,$demand['demand_number']);
                    if (empty($suggest_info)) {
                        continue;
                    }

                    $send_data_temp = array('purchase_order_no'=>$po,'sku'=>$demand['sku'],'num'=>$num,'en_provider_name'=>$trans_res['data']['supplierName'],'en_provider_address'=>$trans_res['data']['contactAddress'],'provider_phone'=>$trans_res['data']['contactNumber'],'cn_provider_name'=>$supplier_info['supplier_name'],'cn_provider_address'=>!empty($supplier_info['register_address'])?$supplier_info['register_address']:$supplier_info['ship_address'],'warehouse_code'=>$suggest_info['destination_warehouse']);

                    $send_data[] = $send_data_temp;

                }

            }


            $wms_url = WAREHOUSE_IP_V2.'/Api/NewTransit/ShipPlan/createTransitMergePdfLabel';//测试时候替换
            $token =json_encode(stockAuth());
            $post_data['data'] = json_encode($send_data);
            $post_data['token'] = $token;


            $send_res = getCurlData($wms_url,$post_data);//翻译结果

            $send_res = json_decode($send_res,true);



            if (!empty($send_res)&&$send_res['status'] == 1) {//推送成功

                if (is_array($send_res['success_list'])&&count($send_res['success_list'])>0) {
                    foreach($send_res['success_list'] as $success){
                        $update_data = array();
                        $su_item = $order_list_arr[$success['purchase_order_no'].'-'.$success['sku']];
                        if ($su_item){//更新物流标签信息
                            $update_data['combine_error_msg'] = '';
                            $update_data['combine_label'] = $success['pdf_url'];
                            $update_data['combine_is_dispose'] = 2;
                            $update_data['combine_update_time'] = date('Y-m-d H:i:s'); ;
                            $update_data['combine_is_update'] = 1;

                            if (!empty($update_data)){
                                $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                                    , array('purchase_number'=>$su_item['purchase_number'],'sku'=>$su_item['sku']));
                            }


                        }

                    }

                }

            }


        }



    }







}