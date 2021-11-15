<?php

/**
 * Created by PhpStorm.
 * 采购单控制器
 * User: Dean
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_label extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_label_model');
        $this->load->model('purchase_order_model');
        $this->load->model('purchase_order_items_model');
        $this->load->model('purchase_suggest_model');
        $this->load->model('supplier_joint_model');



    }


    /*
     * 推送备货单到仓库获取反馈信息
     * /purchase/purchase_label/send_wms_label
     */

    public function send_wms_label(){
        $msg = '推送完成';//推送信息
        $error_msg = '';
       $demand_number = $this->input->get_post('demand_number');
       if(!is_array($demand_number)&&count($demand_number)==0) $this->error_json('数据有误');
        $purchase_arr = array();
        $supplier_code_arr =array();
        foreach ($demand_number as $demand_no) {
            $demand_info = $this->purchase_label_model->get_one($demand_no);
            if (empty($demand_info)) $this->error_json('备货单:'.$demand_no.'不存在');
            $purchase_arr[$demand_info['purchase_number']][] =$demand_info;
            $supplier_code_arr[$demand_info['purchase_number']] = $demand_info['supplier_code'];

        }

//已更新备货单号:
        $updated_demand_number = [];
        $send_data = [];//发送数据
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

            if ($trans_res['code']!=200) $this->error_json('采购单号:'.$po.'供应商信息缺失');

            $supplier_info =  $this->purchase_order_model->purchase_db
                ->select('*')
                ->where('supplier_code',$supplier_code)
                ->get('supplier')
                ->row_array();

            if (empty($supplier_info)) $this->error_json('采购单号:'.$po.'供应商信息缺失');
            foreach ($demand_info as $demand) {
                //拼接请求信息
                $order_item_info = $this->purchase_order_items_model->get_item($demand['purchase_number'], $demand['sku'],true);//采购数量
                $num = $order_item_info['confirm_amount'];

                $send_data_temp = array('purchase_order_no'=>$po,'sku'=>$demand['sku'],'num'=>$num,'en_provider_name'=>$trans_res['data']['supplierName'],'en_provider_address'=>$trans_res['data']['contactAddress'],'provider_phone'=>$trans_res['data']['contactNumber'],'cn_provider_name'=>$supplier_info['supplier_name'],'cn_provider_address'=>!empty($supplier_info['register_address']?$supplier_info['register_address']:$supplier_info['ship_address']));
                $send_data[] = $send_data_temp;


            }

        }
        $wms_url = WMS_DOMAIN.'/Api/Transit/Index/createTransitPdfLabel';//测试时候替换
        //$wms_url= 'http://dp.yibai-it.com:33335/Api/Transit/Index/createTransitPdfLabel';
        $send_data = array('data'=>json_encode($send_data));
        $send_res = getCurlData($wms_url,$send_data);//翻译结果

        if (!empty($send_res)) {
            //更新成功
            $send_res = json_decode($send_res,true);

            if ($send_res['status'] == 1) {//推送成功

              if (is_array($send_res['success_list'])&&count($send_res['success_list'])>0) {
                  foreach($send_res['success_list'] as $success){
                      $update_data = array();
                      $su_item = $this->purchase_label_model->get_label_item($success['purchase_order_no'],$success['sku']);
                      if ($su_item){//更新物流标签信息

                         // $is_ok = false;
                       /*   if (($su_item['purchase_label_down_time']=='0000-00-00 00:00:00')&&($su_item['supplier_down_time']=='0000-00-00 00:00:00'))
                          {
                              $is_ok = true;
                          } elseif (($su_item['purchase_label_down_time']!='0000-00-00 00:00:00')||($su_item['supplier_down_time']!='0000-00-00 00:00:00')) {
                              if (strtotime($success['update_time']>strtotime($su_item['update_time']))) {
                                  $is_ok = true;

                              }

                          } elseif (($su_item['purchase_barcode_down_time']!='0000-00-00 00:00:00')||($su_item['supplier_down_time']!='0000-00-00 00:00:00')) {
                              if (strtotime($success['update_time'] > strtotime($su_item['update_time']))) {
                                  $is_ok = true;

                              }
                          }*/

                         /* if ($is_ok) {*/
                              $update_data['error_mes'] = '';
                              $update_data['barcode'] = $success['barcode_url'];
                              $update_data['label'] = $success['pdf_url'];
                              $update_data['update_time'] = date('Y-m-d H:i:s');

                              $update_data['label_is_dispose'] = 2;
                              $update_data['barcode_is_dispose'] = 2;

                              if (!empty($success['pdf_url']) &&( ($su_item['purchase_label_down_time'] != '0000-00-00 00:00:00') || ($su_item['supplier_down_time'] != '0000-00-00 00:00:00'))) {
                                  if ((strtotime($update_data['update_time']) > strtotime($su_item['purchase_label_down_time'])) || ((strtotime($update_data['update_time']) > strtotime($su_item['supplier_down_time'])))) {
                                      $update_data['label_is_update'] = 1;
                                      $updated_demand_number[] = $su_item['demand_number'];
                                  }

                              }


                             if (!empty($success['barcode_url']) && (($su_item['purchase_barcode_down_time'] != '0000-00-00 00:00:00') || ($su_item['supplier_down_time'] != '0000-00-00 00:00:00'))) {
                                  if ((strtotime($update_data['update_time']) > strtotime($su_item['purchase_barcode_down_time'])) || ((strtotime($update_data['update_time']) > strtotime($su_item['supplier_down_time'])))) {
                                      $update_data['barcode_is_update'] = 1;
                                      $updated_demand_number[] = $su_item['demand_number'];

                                  }
                              }
                          /*} else {
                              $update_data = array();


                          }*/

                          if (!empty($update_data)){
                              $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                                  , array('purchase_number'=>$su_item['purchase_number'],'sku'=>$su_item['sku']));
                          }


                      }

                  }

              }

                if (is_array($send_res['fail_list'])&&count($send_res['fail_list'])>0) {
                    foreach ($send_res['fail_list'] as $fail) {
                        $update_data = array();
                        $update_data['error_mes'] = $fail['message'];
                        $error_msg.=$fail['purchase_order_no'].'_'.$fail['sku'].'获取失败,失败原因:'.$fail['message'].'<br/>';
                        $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                            , array('purchase_number'=>$fail['purchase_order_no'],'sku'=>$fail['sku']));

                    }

                }

            }else{//推送失败
                $this->error_json($send_res['msg']);

            }


        } else {
            $this->error_json('请求仓库接口没有响应');
        }


        $this->success_json($msg.$error_msg);



    }

    /*
     * 推送标签条码标签到门户系统
     * $type int 1为label,2为产品条码
     */
    public function send_provider_label()
    {
        $demand_number = $this->input->get_post('demand_number');
        $type = $this->input->get_post('type');

        if(!is_array($demand_number)&&count($demand_number)==0) $this->error_json('数据有误');
        if (empty($type)) $this->error_json('需要类型');

        foreach ($demand_number as $number ) {

            $demand_info = $this->purchase_label_model->get_one($number);
            if (empty($demand_info)) $this->error_json('条码不存在');
            $send_arr = array();
            $label_url = $demand_info['label'];
            $barcode_url = $demand_info['barcode'];
                if ($type == 1) {
                    if(empty($label_url)) $this->error_json('备货单号:'.$number.'标签为空');

                    $send_data = array('purchaseOrderNo'=>$demand_info['purchase_number'],'sku'=>$demand_info['sku'],'labelPdf'=>$demand_info['label'],'is_sole'=>$demand_info['is_unique']);
                    $send_arr[] = $send_data;

                }elseif($type == 2){
                    if(empty($barcode_url)) $this->error_json('备货单号:'.$number.'产品条码为空');
                    $send_data = array('purchaseOrderNo'=>$demand_info['purchase_number'],'sku'=>$demand_info['sku'],'barcodePdf'=>$demand_info['barcode'],'is_sole'=>$demand_info['is_unique']);
                    $send_arr[] = $send_data;


                }


            }
        $url = SMC_JAVA_API_URL.'/provider/purPush/pushPorivderPostCode';
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;
        $send_res = getCurlData($url, json_encode($send_arr, JSON_UNESCAPED_UNICODE), 'post', $header);
        $send_res = json_decode($send_res,true);



        //返回结果记录门户系统
        $this->supplier_joint_model->RecordGateWayPush($send_res,$demand_number,$send_arr,'SendProviderLabel');


        if (!empty($send_res)&&$send_res['code'] == 200) {
            $update_data = null;
            if ($type == 1) {
                $update_data['label_is_dispose'] = 1;

            } else {
                $update_data['barcode_is_dispose'] = 1;

            }
            foreach ($demand_number as $item) {
                $demand_info = $this->purchase_label_model->get_one($item);
                $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                    , ['demand_number' =>$item]);
                $this->add_log($demand_info,3);

            }


            $this->success_json('推送成功');


        } else {
            $this->error_json('人工推送失败');
        }





    }

    /*
   * 供应商承诺是否贴码
   *
   */

    public function provider_promise_barcode()
    {
        $demand_number = $this->input->get_post('demand_number');
        $is_commitment = $this->input->get_post('is_commitment');
        $remark = $this->input->get_post('remark');//备注

        if(!is_array($demand_number)&&count($demand_number)==0) $this->error_json('数据有误');
        if (empty($is_commitment))  $this->error_json('请选择承诺贴码方式');
        foreach ($demand_number as $number ) {
            $demand_info = $this->purchase_label_model->get_one($number);
            if (empty($demand_info)) $this->error_json('条码不存在');
            //判断入库数量是否为0
            $in_stock_info = $this->purchase_label_model->purchase_db->select('*')->where('purchase_number',$demand_info['purchase_number'])->where('sku',$demand_info['sku'])->get('warehouse_results_main')->row_array();
            if (!empty($in_stock_info['instock_qty'])) $this->error_json('备货单:'.$demand_info['demand_number'].'已入库，不能修改');
            $send_data = array('purchaseOrderNo'=>$demand_info['purchase_number'],'sku'=>$demand_info['sku'],'isCommitment'=>$is_commitment);
            $send_arr[] = $send_data;

        }


        $url = SMC_JAVA_API_URL.'/provider/purPush/pushIsCommitment';
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;

        $send_res = getCurlData($url, json_encode($send_arr, JSON_UNESCAPED_UNICODE), 'post', $header);
        $send_res = json_decode($send_res,true);


        //返回结果记录门户系统
        $this->supplier_joint_model->RecordGateWayPush($send_res,$demand_number,$send_arr,'ProviderPromiseBarcode');

        if (!empty($send_res)&&$send_res['code'] == 200) {
            $update_data = array('is_paste'=>$is_commitment,'remark'=>$remark);
            foreach ($demand_number as $item) {

                $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                    , ['demand_number' =>$item]);

                $this->add_log($demand_info,9,$is_commitment);

            }


            $this->success_json('操作成功');


        } else {
            $this->error_json('承诺贴码操作失败');
        }





    }


    /**
     * 标签列表
     * @author harvin
     * /purchase/purchase_order/get_status_lists
     */
    public function get_label_list()
    {



        $params = [
            'sku' => $this->input->get_post('sku'), // SKU
            'purchase_number' =>$this->input->get_post('purchase_number'),
            'demand_status' => $this->input->get_post('demand_status'),
            'supplier_code' => $this->input->get_post('supplier_code'),
            'is_create' => $this->input->get_post('is_create'),//是否生成标签(1是，2否)
            'is_update' => $this->input->get_post('is_update'),//是否更新 1是2否
            'is_wrong' =>$this->input->get_post('is_wrong'),//是否获取失败(2.否,1.是)
            'shipment_type' =>$this->input->get_post('shipment_type'),//发运类型(1.工厂发运;2.中转仓发运)
            'purchase_is_download' => $this->input->get_post('purchase_is_download'),//采购是否已下载1是2否
            'supplier_is_download' => $this->input->get_post('supplier_is_download'),//供应商是否已下载
            'enable' => $this->input->get_post('enable'),//是否启用门户系统 1禁用2启用
            'is_dispose' =>$this->input->get_post('is_dispose'),//是否处理1否2是
            'order_time_start' => $this->input->get_post('order_time_start'),//下单时间
            'order_time_end' => $this->input->get_post('order_time_end'),
            'update_time_start' => $this->input->get_post('update_time_start'),//最近一次更新时间
            'update_time_end' => $this->input->get_post('update_time_end'),
            'is_promise' => $this->input->get_post('is_promise'),
            'is_plan'    => $this->input->get_post('is_plan'),//是否计划系统推送
            'compact_number'=>$this->input->get_post('compact_number'),
            'new_des_warehouse'=>$this->input->get_post('new_des_warehouse'),
            'is_warehouse_update'=>$this->input->get_post('is_warehouse_update'),

        ];
        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;

        $demand_info = $this->purchase_label_model->get_label_list($params, $offset, $limit, $page);

        $key_arr = ['订单状态','备货单状态','备货单号','sku','采购单号','供应商名称','采购数量','是否退税','是否已生成标签','物流标签内容','获取失败原因','是否已下载','仓库推送时间','下单时间','是否更新','目的仓','已启用门户系统','发运类型','是否计划系统推送'];
        $drop_down_box['demand_status'] = getPurchaseStatus();
        $drop_down_box['is_create'] =[1=>'是',2=>'否'];
        $drop_down_box['is_update'] =[1=>'是',2=>'否'];
        $drop_down_box['is_wrong'] =[1=>'是',2=>'否'];
        $drop_down_box['shipment_type'] =[1=>'工厂发运',2=>'中转仓发运'];
        $drop_down_box['purchase_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['supplier_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['enable'] =[1=>'是',2=>'否'];
        $drop_down_box['is_dispose'] =[1=>'是',2=>'否'];
        $drop_down_box['is_paste'] =[1=>'是',2=>'否',3=>'仓库需换码'];
        $drop_down_box['is_plan'] =[1=>'是',2=>'否'];
        $drop_down_box['is_warehouse_update'] =[1=>'是',2=>'否'];






        $order_status_list = getPurchaseStatus();
        $data_list = $demand_info['data_list'];

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $drop_down_box['new_des_warehouse'] =$warehouse_list;


if($data_list) {
    foreach ($data_list as $value) {
        $value_temp = [];
        $order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);//采购单信息
        $order_item_info = $this->purchase_order_items_model->get_item($value['purchase_number'], $value['sku'],true);
        $suggest_info = $this->purchase_suggest_model->get_one(0,$value['demand_number']);
        $status_name = isset($order_status_list[$order_info['purchase_order_status']])?$order_status_list[$order_info['purchase_order_status']]:NULL;
        $demand_status_name=isset($order_status_list[$suggest_info['suggest_order_status']])?$order_status_list[$suggest_info['suggest_order_status']]:NULL;
        $value_temp['id'] = $value['id'];
        $value_temp['status_name'] = $status_name;//订单状态
        $value_temp['demand_status_name'] = $demand_status_name;//备货单状态
        $value_temp['demand_number'] = $value['demand_number'];
        $value_temp['sku'] = $value['sku'];
        $value_temp['purchase_number'] = $value['purchase_number'];//采购单号
        $value_temp['compact_number'] = $value['compact_number'];//合同单号
        $value_temp['supplier_name'] = $value['supplier_name'];
        $value_temp['num'] = empty($order_item_info['confirm_amount']) ? 0 : $order_item_info['confirm_amount'];//采购数量
        $value_temp['is_drawback'] = $order_info['is_drawback'] == 1 ? '退税' : '否';//是否退税
        $value_temp['is_create'] = !empty($value['label']) ? '是' : '否';
        $value_temp['content'] = $value['label'];
        $value_temp['error_mes'] = $value['error_mes'];
        
        $value_temp['purchase_down'] = $value['purchase_label_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';
        $value_temp['supplier_down'] = $value['supplier_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';

        $value_temp['create_time'] = $value['create_time']; //仓库推送时间
        $value_temp['order_time'] = $value['order_time'];// 下单时间
        $label_is_update = '';
        if ($value['label_is_update'] == 1) {
            $label_is_update .= '是';
            if ($value['label_is_dispose'] == 1) {
                $label_is_update .= '\n' . '已处理';
            } else {
                $label_is_update .= '\n' . '未处理';

            }
        } else {
            $label_is_update .= '否';

        }
        $value_temp['is_update'] = $label_is_update;//

        $destination_warehouse_info = $this->purchase_order_model->purchase_db->select('destination_warehouse_code')->from('shipment_track_list')->where('shipment_type',$suggest_info['shipment_type'])->where('demand_number',$value['demand_number'])->get()->row_array();        //查询是否是发运系统物流跟踪仓库，如果是，显示

        if (!empty($destination_warehouse_info)) {
            $destination_warehouse = $destination_warehouse_info['destination_warehouse_code'];

        } else {
            $destination_warehouse = $value['destination_warehouse'];

        }

        $value_temp['destination_warehouse'] = isset($warehouse_list[$destination_warehouse]) ? $warehouse_list[$destination_warehouse] : '';

        $value_temp['new_des_warehouse'] = isset($value['new_des_warehouse']) ? $warehouse_list[$value['new_des_warehouse']] : '';
        $value_temp['is_warehouse_update'] = $value['new_des_warehouse']==$destination_warehouse?'否':'是';



        $value_temp['enable'] = $value['enable'] == 1 ? '是' : '否';
        $value_temp['shipment_type'] = $order_info['shipment_type'] == 1 ? '工厂发运' : '中转仓发运';
        $value_temp['is_plan'] = $value['source_from'] == 1 ? '是' : '否';


        $data_list_tmp[] = $value_temp;


    }

        $data_list = $data_list_tmp;
        unset($data_list_tmp);
}

        $this->success_json(['key' => $key_arr,'values' => $data_list,'drop_down_box' => $drop_down_box],$demand_info['page_data']);


    }



    /**
     * 条码列表
     * @author harvin
     * /purchase/purchase_order/get_status_lists
     */
    public function get_barcode_list()
    {
        $params = [
            'sku' => $this->input->get_post('sku'), // SKU
            'purchase_number' =>$this->input->get_post('purchase_number'),
            'demand_status' => $this->input->get_post('demand_status'),
            'supplier_code' => $this->input->get_post('supplier_code'),
            'is_create' => $this->input->get_post('is_create'),//是否生成条码(1是，2否)
            'is_update' => $this->input->get_post('is_update'),//是否更新 1是2否
            'is_wrong' =>$this->input->get_post('is_wrong'),//是否获取失败(2.否,1.是)
            'shipment_type' =>$this->input->get_post('shipment_type'),//发运类型(1.工厂发运;2.中转仓发运)
            'purchase_is_download' => $this->input->get_post('purchase_is_download'),//采购是否已下载1是2否
            'supplier_is_download' => $this->input->get_post('supplier_is_download'),//供应商是否已下载
            'enable' => $this->input->get_post('enable'),//是否启用门户系统 1禁用2启用
            'is_dispose' =>$this->input->get_post('is_dispose'),//是否处理1否2是
            'is_paste' =>$this->input->get_post('is_paste'),//是否承诺贴码
            'order_time_start' => $this->input->get_post('order_time_start'),//下单时间
            'order_time_end' => $this->input->get_post('order_time_end'),
            'update_time_start' => $this->input->get_post('update_time_start'),//最近一次更新时间
            'update_time_end' => $this->input->get_post('update_time_end'),
            'is_promise' => $this->input->get_post('is_promise'),
            'is_plan'    => $this->input->get_post('is_plan'),
            'compact_number'=>$this->input->get_post('compact_number'),
            'new_des_warehouse'=>$this->input->get_post('new_des_warehouse'),
            'is_warehouse_update'=>$this->input->get_post('is_warehouse_update')

        ];

        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;



        $demand_info = $this->purchase_label_model->get_barcode_list($params, $offset, $limit, $page);

        $key_arr = ['订单状态','备货单状态','备货单号','sku','采购单号','供应商名称','采购数量','是否退税','是否已生成条码','产品条码内容','条码是否唯一','获取失败原因','是否已下载','仓库推送时间','下单时间','是否更新','目的仓','已启用门户系统','发运类型','是否承诺贴码','是否计划系统推送'];
        $drop_down_box['demand_status'] = getPurchaseStatus();
        $drop_down_box['is_create'] =[1=>'是',2=>'否'];
        $drop_down_box['is_update'] =[1=>'是',2=>'否'];
        $drop_down_box['is_wrong'] =[1=>'是',2=>'否'];
        $drop_down_box['shipment_type'] =[1=>'工厂发运',2=>'中转仓发运'];
        $drop_down_box['purchase_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['supplier_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['enable'] =[1=>'是',2=>'否'];
        $drop_down_box['is_dispose'] =[1=>'是',2=>'否'];
        $drop_down_box['is_paste'] =[1=>'是',2=>'否',3=>'仓库需换码'];
        $drop_down_box['is_plan'] =[1=>'是',2=>'否'];
        $drop_down_box['is_warehouse_update'] =[1=>'是',2=>'否'];


        $order_status_list = getPurchaseStatus();

        $data_list = $demand_info['data_list'];

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $drop_down_box['new_des_warehouse'] =$warehouse_list;



if ($data_list) {
    foreach ($data_list as $value) {
        $value_temp = [];
        $order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);//采购单信息
        $order_item_info = $this->purchase_order_items_model->get_item($value['purchase_number'], $value['sku'],true);
        $suggest_info = $this->purchase_suggest_model->get_one(0,$value['demand_number']);
        $status_name = isset($order_status_list[$order_info['purchase_order_status']])?$order_status_list[$order_info['purchase_order_status']]:NULL;
        $demand_status_name=isset($order_status_list[$suggest_info['suggest_order_status']])?$order_status_list[$suggest_info['suggest_order_status']]:NULL;
        $value_temp['id'] = $value['id'];
        $value_temp['status_name'] = $status_name;//订单状态
        $value_temp['demand_status_name'] = $demand_status_name;//备货单状态
        $value_temp['demand_number'] = $value['demand_number'];
        $value_temp['sku'] = $value['sku'];
        $value_temp['purchase_number'] = $value['purchase_number'];//采购单号
        $value_temp['compact_number'] = $value['compact_number'];//合同单号
        $value_temp['supplier_name'] = $value['supplier_name'];
        $value_temp['num'] = empty($order_item_info['confirm_amount']) ? 0 : $order_item_info['confirm_amount'];//采购数量
        $value_temp['is_drawback'] = $order_info['is_drawback'] == 1 ? '退税' : '否';//是否退税
        $value_temp['is_create'] = !empty($value['barcode']) ? '是' : '否';
        $value_temp['content'] = $value['barcode'];
        $value_temp['is_unique'] = $value['is_unique'] == 1 ? '是' : '否';//条码是否唯一
        $value_temp['error_mes'] = $value['error_mes'];

        $value_temp['purchase_down'] = $value['purchase_label_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';
        $value_temp['supplier_down'] = $value['supplier_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';

        $value_temp['create_time'] = $value['create_time']; //仓库推送时间
        $value_temp['order_time'] = $value['order_time'];// 下单时间
        $barcode_is_update = '';
        if ($value['barcode_is_update'] == 1) {
            $barcode_is_update .= '是';
            if ($value['barcode_is_dispose'] == 1) {
                $barcode_is_update .= '\n' . '已处理';
            } else {
                $barcode_is_update .= '\n' . '未处理';

            }
        } else {
            $barcode_is_update .= '否';

        }
        $value_temp['is_update'] = $barcode_is_update;



        $is_paste =$drop_down_box['is_paste'][$value['is_paste']];


        $value_temp['is_paste'] = $is_paste . '\n' . $value['remark'];

        $destination_warehouse_info = $this->purchase_order_model->purchase_db->select('destination_warehouse_code')->from('shipment_track_list')->where('shipment_type',$suggest_info['shipment_type'])->where('demand_number',$value['demand_number'])->get()->row_array();        //查询是否是发运系统物流跟踪仓库，如果是，显示

        if (!empty($destination_warehouse_info)) {
            $destination_warehouse = $destination_warehouse_info['destination_warehouse_code'];

        } else {
            $destination_warehouse = $value['destination_warehouse'];

        }

        $value_temp['destination_warehouse'] = isset($warehouse_list[$destination_warehouse]) ? $warehouse_list[$destination_warehouse] : '';


        $value_temp['new_des_warehouse'] = isset($value['new_des_warehouse']) ? $warehouse_list[$value['new_des_warehouse']] : '';
        $value_temp['is_warehouse_update'] = $value['new_des_warehouse']==$destination_warehouse?'否':'是';

        $value_temp['enable'] = $value['enable'] == 1 ? '是' : '否';
        $value_temp['shipment_type'] = $order_info['shipment_type'] == 1 ? '工厂发运' : '中转仓发运';
        $value_temp['is_plan'] = $value['source_from'] == 1 ? '是' : '否';

        $data_list_tmp[] = $value_temp;


    }

            $data_list = $data_list_tmp;
            unset($data_list_tmp);
}

        $this->success_json(['key' => $key_arr,'values' => $data_list,'drop_down_box' => $drop_down_box],$demand_info['page_data']);


    }

    /*
     * 添加操作日志
     * $demand_info array 标签信息 $type int 操作方式
     */
    public function add_log($demand_info,$type,$data='')
    {

        $insert_data = array(
            'opr_user' =>getActiveUserName(),
            'opr_time' =>date('Y-m-d H:i:s'),
            'opr_type'=>$type,
            'purchase_number'=>$demand_info['purchase_number'],
            'sku'=>$demand_info['sku']


        );
        if ($type==9) {
            $insert_data['old_label_pdf'] = $demand_info['is_paste'];
            $insert_data['label_pdf'] = $data;

        }

        $result = $this->purchase_label_model->purchase_db->insert('pur_order_barcodepdf_log',$insert_data);

    }


    /**
     * 标签列表导出
     * @author Jeff
     */
    public function export_label(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->helper('export_csv');
        $ids = $this->input->get_post('id');
        $ids_arr = explode(',', $ids);

        if(!empty($ids)){
            $params['id']   = $ids_arr;
        }else{
            $params = [
                'sku' => $this->input->get_post('sku'), // SKU
                'purchase_number' =>$this->input->get_post('purchase_number'),
                'demand_status' => $this->input->get_post('demand_status'),
                'supplier_code' => $this->input->get_post('supplier_code'),
                'is_create' => $this->input->get_post('is_create'),//是否生成标签(1是，2否)
                'is_update' => $this->input->get_post('is_update'),//是否更新 1是2否
                'is_wrong' =>$this->input->get_post('is_wrong'),//是否获取失败(2.否,1.是)
                'shipment_type' =>$this->input->get_post('shipment_type'),//发运类型(1.工厂发运;2.中转仓发运)
                'purchase_is_download' => $this->input->get_post('purchase_is_download'),//采购是否已下载1是2否
                'supplier_is_download' => $this->input->get_post('supplier_is_download'),//供应商是否已下载
                'enable' => $this->input->get_post('enable'),//3 1禁用2启用
                'is_dispose' =>$this->input->get_post('is_dispose'),//是否处理1否2是
                'order_time_start' => $this->input->get_post('order_time_start'),//下单时间
                'order_time_end' => $this->input->get_post('order_time_end'),
                'update_time_start' => $this->input->get_post('update_time_start'),//最近一次更新时间
                'update_time_end' => $this->input->get_post('update_time_end'),
                'is_promise' => $this->input->get_post('is_promise'),
                'is_plan'    => $this->input->get_post('is_plan'),
                'compact_number'=>$this->input->get_post('compact_number'),
                'new_des_warehouse'=>$this->input->get_post('new_des_warehouse'),
                'is_warehouse_update'=>$this->input->get_post('is_warehouse_update')


            ];
        }
        $demand_info = $this->purchase_label_model->get_label_list($params,0,9500,1,true);


        $purchase_tax_list_export = $demand_info['data_list'];

  /*      $this->load->model('warehouse/Logistics_type_model');
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type_list = array_column($logistics_type_list,'type_name','type_code');*/

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');

  /*      $this->load->model('system/Reason_config_model');
        $param['status'] = 1;//启用的
        $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
        $category_list = array_column($cancel_reason_category_list['values'],'reason_name','id');
        $skus = array_column( $demand_info['data_list'],"sku");*/
        //$tax_list_tmp = [];
        $order_status_list = getPurchaseStatus();
        $tax_list_tmp=[];

        if($purchase_tax_list_export){
            foreach($purchase_tax_list_export as $value){

                $value_temp = [];
                $order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);//采购单信息
                $order_item_info = $this->purchase_order_items_model->get_item($value['purchase_number'], $value['sku'],true);
                $suggest_info = $this->purchase_suggest_model->get_one(0,$value['demand_number']);
                $status_name = isset($order_status_list[$order_info['purchase_order_status']])?$order_status_list[$order_info['purchase_order_status']]:NULL;
                $demand_status_name=isset($order_status_list[$suggest_info['suggest_order_status']])?$order_status_list[$suggest_info['suggest_order_status']]:NULL;
                $value_temp['status_name'] = $status_name;//订单状态
                $value_temp['demand_status_name'] = $demand_status_name;//备货单状态
                $value_temp['demand_number'] = $value['demand_number'];
                $value_temp['sku'] = $value['sku'];
                $value_temp['purchase_number'] = $value['purchase_number'];//采购单号
                $value_temp['compact_number'] = $value['compact_number'];//合同单号
                $value_temp['supplier_name'] = $value['supplier_name'];
                $value_temp['num'] = empty($order_item_info['confirm_amount']) ? 0 : $order_item_info['confirm_amount'];//采购数量
                $value_temp['is_drawback'] = $order_info['is_drawback'] == 1 ? '退税' : '否';//是否退税
                $value_temp['is_create'] = !empty($value['label']) ? '是' : '否';
                $value_temp['content'] = $value['label'];
                $value_temp['error_mes'] = $value['error_mes'];

                $value_temp['purchase_down'] = $value['purchase_label_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';
                $value_temp['supplier_down'] = $value['supplier_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';

                $value_temp['create_time'] = $value['create_time']; //仓库推送时间
                $value_temp['order_time'] = $value['order_time'];// 下单时间
                $label_is_update = '';
                if ($value['label_is_update'] == 1) {
                    $label_is_update .= '是';
                    if ($value['label_is_dispose'] == 1) {
                        $label_is_update .= '\n' . '已处理';
                    } else {
                        $label_is_update .= '\n' . '未处理';

                    }
                } else {
                    $label_is_update .= '否';

                }
                $value_temp['is_update'] = $label_is_update;// 下单时间
                $destination_warehouse_info = $this->purchase_order_model->purchase_db->select('destination_warehouse_code')->from('shipment_track_list')->where('shipment_type',$suggest_info['shipment_type'])->where('demand_number',$value['demand_number'])->get()->row_array();        //查询是否是发运系统物流跟踪仓库，如果是，显示

                if (!empty($destination_warehouse_info)) {
                    $destination_warehouse = $destination_warehouse_info['destination_warehouse_code'];

                } else {
                    $destination_warehouse = $value['destination_warehouse'];

                }

                $value_temp['destination_warehouse'] = isset($warehouse_list[$destination_warehouse]) ? $warehouse_list[$destination_warehouse] : '';

                $value_temp['new_des_warehouse'] = isset($value['new_des_warehouse']) ? $warehouse_list[$value['new_des_warehouse']] : '';
                $value_temp['is_warehouse_update'] = $value['new_des_warehouse']==$destination_warehouse?'否':'是';
                $value_temp['enable'] = $value['enable'] == 1 ? '是' : '否';
                $value_temp['shipment_type'] = $order_info['shipment_type'] == 1 ? '工厂发运' : '中转仓发运';
                $value_temp['is_plan'] = $value['source_from'] == 1 ? '是' : '否';



                $tax_list_tmp[] = $value_temp;

            }
        }



        $this->success_json($tax_list_tmp);
    }


    /**
     * 未审核采购需求单列表导出
     * @author Jeff
     */
    public function export_barcode(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->helper('export_csv');
        $ids = $this->input->get_post('id');
        $ids_arr = explode(',', $ids);

        $is_pasteArr = [1=>'是',2=>'否','3'=>'仓库需换码'];

        if(!empty($ids)){
            $params['id']   = $ids_arr;
        }else{
            $params = [
                'sku' => $this->input->get_post('sku'), // SKU
                'purchase_number' =>$this->input->get_post('purchase_number'),
                'demand_status' => $this->input->get_post('demand_status'),
                'supplier_code' => $this->input->get_post('supplier_code'),
                'is_create' => $this->input->get_post('is_create'),//是否生成标签(1是，2否)
                'is_update' => $this->input->get_post('is_update'),//是否更新 1是2否
                'is_wrong' =>$this->input->get_post('is_wrong'),//是否获取失败(2.否,1.是)
                'shipment_type' =>$this->input->get_post('shipment_type'),//发运类型(1.工厂发运;2.中转仓发运)
                'purchase_is_download' => $this->input->get_post('purchase_is_download'),//采购是否已下载1是2否
                'supplier_is_download' => $this->input->get_post('supplier_is_download'),//供应商是否已下载
                'enable' => $this->input->get_post('enable'),//是否启用门户系统 1禁用2启用
                'is_dispose' =>$this->input->get_post('is_dispose'),//是否处理1否2是
                'order_time_start' => $this->input->get_post('order_time_start'),//下单时间
                'order_time_end' => $this->input->get_post('order_time_end'),
                'update_time_start' => $this->input->get_post('update_time_start'),//最近一次更新时间
                'update_time_end' => $this->input->get_post('update_time_end'),
                'is_promise' => $this->input->get_post('is_promise'),
                'is_plan'    =>  $this->input->get_post('is_plan'),
                'compact_number'=>$this->input->get_post('compact_number'),
                'new_des_warehouse'=>$this->input->get_post('new_des_warehouse'),
                'is_warehouse_update'=>$this->input->get_post('is_warehouse_update'),


            ];
        }
        $demand_info = $this->purchase_label_model->get_barcode_list($params,0,9500,1,true);
        $purchase_tax_list_export = $demand_info['data_list'];

        /*      $this->load->model('warehouse/Logistics_type_model');
              $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
              $logistics_type_list = array_column($logistics_type_list,'type_name','type_code');*/

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');

        /*      $this->load->model('system/Reason_config_model');
              $param['status'] = 1;//启用的
              $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
              $category_list = array_column($cancel_reason_category_list['values'],'reason_name','id');
              $skus = array_column( $demand_info['data_list'],"sku");*/
        //$tax_list_tmp = [];
        $order_status_list = getPurchaseStatus();

        if($purchase_tax_list_export){
            foreach($purchase_tax_list_export as $value){

                $value_temp = [];
                $order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);//采购单信息
                $order_item_info = $this->purchase_order_items_model->get_item($value['purchase_number'], $value['sku'],true);
                $suggest_info = $this->purchase_suggest_model->get_one(0,$value['demand_number']);
                $status_name = isset($order_status_list[$order_info['purchase_order_status']])?$order_status_list[$order_info['purchase_order_status']]:NULL;
                $demand_status_name=isset($order_status_list[$suggest_info['suggest_order_status']])?$order_status_list[$suggest_info['suggest_order_status']]:NULL;
                $value_temp['status_name'] = $status_name;//订单状态
                $value_temp['demand_status_name'] = $demand_status_name;//备货单状态
                $value_temp['demand_number'] = $value['demand_number'];
                $value_temp['sku'] = $value['sku'];
                $value_temp['purchase_number'] = $value['purchase_number'];//采购单号
                $value_temp['compact_number'] = $value['compact_number'];

                $value_temp['supplier_name'] = $value['supplier_name'];
                $value_temp['num'] = empty($order_item_info['confirm_amount']) ? 0 : $order_item_info['confirm_amount'];//采购数量
                $value_temp['is_drawback'] = $order_info['is_drawback'] == 1 ? '退税' : '否';//是否退税
                $value_temp['is_create'] = !empty($value['barcode']) ? '是' : '否';
                $value_temp['content'] = $value['barcode'];
                $value_temp['is_unique'] = $value['is_unique'] == 1 ? '是' : '否';//条码是否唯一
                $value_temp['error_mes'] = $value['error_mes'];

                $value_temp['purchase_down'] = $value['purchase_barcode_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';
                $value_temp['supplier_down'] = $value['supplier_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';

                $value_temp['create_time'] = $value['create_time']; //仓库推送时间
                $value_temp['order_time'] = $value['order_time'];// 下单时间
                $barcode_is_update = '';
                if ($value['barcode_is_update'] == 1) {
                    $barcode_is_update .= '是';
                    if ($value['barcode_is_dispose'] == 1) {
                        $barcode_is_update .= '\n' . '已处理';
                    } else {
                        $barcode_is_update .= '\n' . '未处理';

                    }
                } else {
                    $barcode_is_update .= '否';

                }
                $value_temp['is_update'] = $barcode_is_update;
                $destination_warehouse_info = $this->purchase_order_model->purchase_db->select('destination_warehouse_code')->from('shipment_track_list')->where('shipment_type',$suggest_info['shipment_type'])->where('demand_number',$value['demand_number'])->get()->row_array();        //查询是否是发运系统物流跟踪仓库，如果是，显示

                if (!empty($destination_warehouse_info)) {
                    $destination_warehouse = $destination_warehouse_info['destination_warehouse_code'];

                } else {
                    $destination_warehouse = $value['destination_warehouse'];

                }

                $value_temp['destination_warehouse'] = isset($warehouse_list[$destination_warehouse]) ? $warehouse_list[$destination_warehouse] : '';
                $value_temp['new_des_warehouse'] = isset($value['new_des_warehouse']) ? $warehouse_list[$value['new_des_warehouse']] : '';
                $value_temp['is_warehouse_update'] = $value['new_des_warehouse']==$destination_warehouse?'否':'是';
                $value_temp['enable'] = $value['enable'] == 1 ? '是' : '否';
                $value_temp['shipment_type'] = $order_info['shipment_type'] == 1 ? '工厂发运' : '中转仓发运';
                $is_paste =$is_pasteArr[$value['is_paste']];
                $value_temp['is_paste'] = $is_paste . '\n' . $value['remark'];
                $value_temp['is_plan'] = $value['source_from'] == 1 ? '是' : '否';

                $tax_list_tmp[] = $value_temp;


            }
        }
        $this->success_json($tax_list_tmp);
    }


    /**
     * 变更日志列表
     * @author harvin
     * /purchase/purchase_order/get_status_lists
     */
    public function get_pdf_log_list()
    {
        $params = [


        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        $demand_info = $this->purchase_label_model->get_pdf_log_list($params, $offset, $limit, $page);
        $key_arr = ['订单状态','备货单号','sku','采购单号','供应商名称','采购数量','是否退税','是否已生成标签','物流标签内容','获取失败原因','是否已下载','仓库推送时间','下单时间','是否更新','目的仓','已启用门户系统','发运类型'];
        $drop_down_box['demand_status'] = getSuggestStatus();
        $drop_down_box['is_create'] =[1=>'是',2=>'否'];
        $drop_down_box['is_update'] =[1=>'是',2=>'否'];
        $drop_down_box['is_wrong'] =[1=>'是',2=>'否'];
        $drop_down_box['shipment_type'] =[1=>'工厂发运',2=>'中转仓发运'];
        $drop_down_box['purchase_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['supplier_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['enable'] =[1=>'是',2=>'否'];
        $drop_down_box['is_dispose'] =[1=>'是',2=>'否'];
        $drop_down_box['is_paste'] =[1=>'是',2=>'否'];


        $order_status_list = getPurchaseStatus();
        $data_list = $demand_info['data_list'];

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');


        foreach ($data_list as $value) {
            $value_temp=[];
            $order_info = $this->purchase_order_model->get_one($value['purchase_number'],false);//采购单信息
            $status_name = $order_status_list[$order_info['purchase_order_status']];
            $value_temp['status_name']  = $status_name;//订单状态
            $value_temp['demand_number'] = $value['demand_number'];
            $value_temp['sku'] = $value['sku'];
            $value_temp['purchase_number'] = $value['purchase_number'];//采购单号
            $value_temp['num'] = $value['purchase_amount'];//采购数量
            $value_temp['is_drawback'] = $order_info['is_drawback']==1?'退税':'否';//是否退税
            $value_temp['is_create'] = !empty($value['barcode'])?'是':'否';
            $value_temp['content'] = $value['label'];
            $value_temp['error_mes'] = $value['error_mes'];

            $value_temp['purchase_down'] = $value['purchase_label_down_time']!='0000-00-00 00:00:00' ?'是':'否';
            $value_temp['supplier_down'] = $value['supplier_down_time']!='0000-00-00 00:00:00' ?'是':'否';

            $value_temp['create_time'] = $value['create_time']; //仓库推送时间
            $value_temp['order_time']= $value['order_time'];// 下单时间
            $barcode_is_update = '';
            if($value['barcode_is_update']==1){
                $barcode_is_update.='是';
                if ($value['barcode_is_dispose'] == 1) {
                    $barcode_is_update.='\n'.'已处理';
                } else {
                    $barcode_is_update.='\n'.'未处理';

                }
            }else{
                $barcode_is_update.='否';

            }
            $value_temp['is_update'] = $barcode_is_update;

            $is_paste = $value['is_paste'] == 1?'是':'否';

            $value_temp['is_paste'] = $is_paste.'\n'.$value['remark'];

            $value_temp['destination_warehouse']    = isset($warehouse_list[$value['destination_warehouse']])?$warehouse_list[$value['destination_warehouse']]:'';
            $value_temp['enable'] = $value['enable'] == 1?'是':'否';
            $value_temp['shipment_type'] = $order_info['shipment_type'] == 1?'工厂发运':'中转仓发运';

            $data_list_tmp[] = $value_temp;


        }

        $data_list = $data_list_tmp;
        unset($data_list_tmp);

        $this->success_json(['key' => $key_arr,'values' => $data_list,'drop_down_box' => $drop_down_box],$demand_info['page_data']);


    }




    /*
 * 获取二合一信息
 * /purchase/purchase_label/send_wms_label
 */

    public function send_combine_label(){
        $msg = '推送完成';//推送信息
        $error_msg = '';
        $demand_number = $this->input->get_post('demand_number');
        if(!is_array($demand_number)&&count($demand_number)==0) $this->error_json('数据有误');
        $purchase_arr = array();
        $supplier_code_arr =array();
        foreach ($demand_number as $demand_no) {
            $demand_info = $this->purchase_label_model->get_one($demand_no);
            if (empty($demand_info)) $this->error_json('备货单:'.$demand_no.'不存在');
            $purchase_arr[$demand_info['purchase_number']][] =$demand_info;
            $supplier_code_arr[$demand_info['purchase_number']] = $demand_info['supplier_code'];

        }

//已更新备货单号:
        $updated_demand_number = [];
        $send_data = [];//发送数据
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



            if ($trans_res['code']!=200) $this->error_json('采购单号:'.$po.'供应商信息缺失');
            //临时调整，后续换回来
            $temp_supplier_arr = ['A1448676093'];
            if (in_array($supplier_code,$temp_supplier_arr)) {
                $trans_res['data']['contactAddress'] ='Tangnan Industrial Development Zone, Songgang, Shishan Town, Nanhai District,Foshan City';

            }

            $supplier_info =  $this->purchase_order_model->purchase_db
                ->select('*')
                ->where('supplier_code',$supplier_code)
                ->get('supplier')
                ->row_array();



            if (empty($supplier_info)) $this->error_json('采购单号:'.$po.'供应商信息缺失');


            foreach ($demand_info as $demand) {
                //拼接请求信息
                $order_item_info = $this->purchase_order_items_model->get_item($demand['purchase_number'], $demand['sku'],true);//采购数量
                $num = $order_item_info['confirm_amount'];

                $suggest_info = $this->purchase_suggest_model->get_one(0,$demand['demand_number']);

                $send_data_temp = array('purchase_order_no'=>$po,'sku'=>$demand['sku'],'num'=>$num,'en_provider_name'=>$trans_res['data']['supplierName'],'en_provider_address'=>$trans_res['data']['contactAddress'],'provider_phone'=>$trans_res['data']['contactNumber'],'cn_provider_name'=>$supplier_info['supplier_name'],'cn_provider_address'=>!empty($supplier_info['register_address'])?$supplier_info['register_address']:$supplier_info['ship_address'],'warehouse_code'=>$suggest_info['destination_warehouse']);
                $send_data[] = $send_data_temp;


            }

        }
        $wms_url = WMS_DOMAIN.'/Api/Transit/Index/createTransitPdfLabel';//测试时候替换

        //$wms_url= 'http://dp.yibai-it.com:33335/Api/Transit/Index/createTransitPdfLabel';
        $send_data = array('data'=>json_encode($send_data));

        $send_res = getCurlData($wms_url,$send_data);//翻译结果



        if (!empty($send_res)) {
            //更新成功
            $send_res = json_decode($send_res,true);
            if ($send_res['status'] == 1) {//推送成功

                if (is_array($send_res['success_list'])&&count($send_res['success_list'])>0) {
                    foreach($send_res['success_list'] as $success){
                        $update_data = array();
                        $su_item = $this->purchase_label_model->get_label_item($success['purchase_order_no'],$success['sku']);

                        if ($su_item){//更新物流标签信息
                            $update_data['error_mes'] = '';
                            $update_data['combine_label'] = $success['pdf_url'];

                            if (!empty($update_data)){
                                $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                                    , array('purchase_number'=>$su_item['purchase_number'],'sku'=>$su_item['sku']));
                            }


                        }

                    }

                }

                if (is_array($send_res['fail_list'])&&count($send_res['fail_list'])>0) {
                    foreach ($send_res['fail_list'] as $fail) {
                        $update_data = array();
                        $update_data['error_mes'] = $fail['message'];
                        $error_msg.=$fail['purchase_order_no'].'_'.$fail['sku'].'获取失败,失败原因:'.$fail['message'].'<br/>';
                        $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                            , array('purchase_number'=>$fail['purchase_order_no'],'sku'=>$fail['sku']));

                    }

                }

            }else{//推送失败
                $this->error_json($send_res['msg']);

            }


        } else {
            $this->error_json('请求仓库接口没有响应');
        }

        $this->success_json($msg.$error_msg);



    }



    /**
     * 条码列表
     * @author harvin
     * /purchase/purchase_order/get_status_lists
     */
    public function get_combine_list()
    {
        $params = [
            'sku' => $this->input->get_post('sku'), // SKU
            'purchase_number' =>$this->input->get_post('purchase_number'),
            'demand_status' => $this->input->get_post('demand_status'),
            'supplier_code' => $this->input->get_post('supplier_code'),
            'is_create' => $this->input->get_post('is_create'),//是否生成条码(1是，2否)
            'is_update' => $this->input->get_post('is_update'),//是否更新 1是2否
            'is_wrong' =>$this->input->get_post('is_wrong'),//是否获取失败(2.否,1.是)
            'shipment_type' =>$this->input->get_post('shipment_type'),//发运类型(1.工厂发运;2.中转仓发运)
            'purchase_is_download' => $this->input->get_post('purchase_is_download'),//采购是否已下载1是2否
            'supplier_is_download' => $this->input->get_post('supplier_is_download'),//供应商是否已下载
            'enable' => $this->input->get_post('enable'),//是否启用门户系统 1禁用2启用
            'is_dispose' =>$this->input->get_post('is_dispose'),//是否处理1否2是
            'is_paste' =>$this->input->get_post('is_paste'),//是否承诺贴码
            'order_time_start' => $this->input->get_post('order_time_start'),//下单时间
            'order_time_end' => $this->input->get_post('order_time_end'),
            'update_time_start' => $this->input->get_post('update_time_start'),//最近一次更新时间
            'update_time_end' => $this->input->get_post('update_time_end'),
            'is_promise' => $this->input->get_post('is_promise'),
            'is_plan'    => $this->input->get_post('is_plan'),
            'compact_number'=>$this->input->get_post('compact_number'),
            'new_des_warehouse'=>$this->input->get_post('new_des_warehouse'),
            'is_warehouse_update'=>$this->input->get_post('is_warehouse_update'),
            'is_charged'         =>$this->input->get_post('is_charged')

        ];

        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;



        $demand_info = $this->purchase_label_model->get_combine_list($params, $offset, $limit, $page);

        $key_arr = ['订单状态','备货单状态','备货单号','sku','采购单号','供应商名称','采购数量','是否退税','是否已生成条码','产品条码内容','条码是否唯一','获取失败原因','是否已下载','仓库推送时间','下单时间','是否更新','目的仓','已启用门户系统','发运类型','是否承诺贴码','是否计划系统推送'];
        $drop_down_box['demand_status'] = getPurchaseStatus();
        $drop_down_box['is_create'] =[1=>'是',2=>'否'];
        $drop_down_box['is_update'] =[1=>'是',2=>'否'];
        $drop_down_box['is_wrong'] =[1=>'是',2=>'否'];
        $drop_down_box['shipment_type'] =[1=>'工厂发运',2=>'中转仓发运'];
        $drop_down_box['purchase_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['supplier_is_download'] =[1=>'是',2=>'否'];
        $drop_down_box['enable'] =[1=>'是',2=>'否'];
        $drop_down_box['is_dispose'] =[1=>'是',2=>'否'];
        $drop_down_box['is_paste'] =[1=>'是',2=>'否',3=>'仓库需换码'];
        $drop_down_box['is_plan'] =[1=>'是',2=>'否'];
        $drop_down_box['is_warehouse_update'] =[1=>'是',2=>'否'];
        $drop_down_box['is_charged'] =[1=>'是',2=>'否'];




        $order_status_list = getPurchaseStatus();

        $data_list = $demand_info['data_list'];

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $drop_down_box['new_des_warehouse'] =$warehouse_list;





        if ($data_list) {
            $is_charged_list = $this->purchase_label_model->check_is_charged(array_unique(array_column($data_list,'sku')));
            foreach ($data_list as $value) {
                $value_temp = [];
                $order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);//采购单信息
                $order_item_info = $this->purchase_order_items_model->get_item($value['purchase_number'], $value['sku'],true);
                $suggest_info = $this->purchase_suggest_model->get_one(0,$value['demand_number']);
                $status_name = isset($order_status_list[$order_info['purchase_order_status']])?$order_status_list[$order_info['purchase_order_status']]:NULL;
                $demand_status_name=isset($order_status_list[$suggest_info['suggest_order_status']])?$order_status_list[$suggest_info['suggest_order_status']]:NULL;
                $value_temp['id'] = $value['id'];
                $value_temp['status_name'] = $status_name;//订单状态
                $value_temp['demand_status_name'] = $demand_status_name;//备货单状态
                $value_temp['demand_number'] = $value['demand_number'];
                $value_temp['sku'] = $value['sku'];
                $value_temp['purchase_number'] = $value['purchase_number'];//采购单号
                $value_temp['compact_number'] = $value['compact_number'];//合同单号

                $value_temp['supplier_name'] = $value['supplier_name'];
                $value_temp['num'] = empty($order_item_info['confirm_amount']) ? 0 : $order_item_info['confirm_amount'];//采购数量
                $value_temp['is_drawback'] = $order_info['is_drawback'] == 1 ? '退税' : '否';//是否退税
                $value_temp['is_create'] = !empty($value['combine_label']) ? '是' : '否';
                $value_temp['content'] = $value['combine_label'];
                $value_temp['is_unique'] = $value['is_unique'] == 1 ? '是' : '否';//条码是否唯一
                $value_temp['error_mes'] = $value['combine_error_msg'];

                $value_temp['purchase_down'] = $value['purchase_label_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';
                $value_temp['supplier_down'] = $value['supplier_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';

                $value_temp['create_time'] = $value['create_time']; //仓库推送时间
                $value_temp['order_time'] = $value['order_time'];// 下单时间
                $barcode_is_update = '';
                if ($value['combine_is_update'] == 1) {
                    $barcode_is_update .= '是';
                    if ($value['combine_is_update'] == 1) {
                        $barcode_is_update .= '\n' . '已处理';
                    } else {
                        $barcode_is_update .= '\n' . '未处理';

                    }
                } else {
                    $barcode_is_update .= '否';

                }
                $value_temp['is_update'] = $barcode_is_update;



                $is_paste =$drop_down_box['is_paste'][$value['is_paste']];


                $value_temp['is_paste'] = $is_paste . '\n' . $value['remark'];

                $destination_warehouse_info = $this->purchase_order_model->purchase_db->select('destination_warehouse_code')->from('shipment_track_list')->where('shipment_type',$suggest_info['shipment_type'])->where('demand_number',$value['demand_number'])->get()->row_array();        //查询是否是发运系统物流跟踪仓库，如果是，显示

                if (!empty($destination_warehouse_info)) {
                    $destination_warehouse = $destination_warehouse_info['destination_warehouse_code'];

                } else {
                    $destination_warehouse = $value['destination_warehouse'];

                }

                $value_temp['destination_warehouse'] = isset($warehouse_list[$destination_warehouse]) ? $warehouse_list[$destination_warehouse] : '';

                $value_temp['new_des_warehouse'] = isset($value['new_des_warehouse']) ? $warehouse_list[$value['new_des_warehouse']] : '';
                $value_temp['is_warehouse_update'] = $value['new_des_warehouse']==$destination_warehouse?'否':'是';

                $value_temp['enable'] = $value['enable'] == 1 ? '是' : '否';
                $value_temp['shipment_type'] = $order_info['shipment_type'] == 1 ? '工厂发运' : '中转仓发运';
                $value_temp['is_plan'] = $value['source_from'] == 1 ? '是' : '否';
                $value_temp['is_charged'] = in_array($value['sku'],$is_charged_list)?'是':'否';

                $data_list_tmp[] = $value_temp;

            }

            $data_list = $data_list_tmp;
            unset($data_list_tmp);
        }

        $this->success_json(['key' => $key_arr,'values' => $data_list,'drop_down_box' => $drop_down_box],$demand_info['page_data']);


    }


    /**
     * 未审核采购需求单列表导出
     * @author Jeff
     */
    public function export_combine(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->helper('export_csv');
        $ids = $this->input->get_post('id');
        $ids_arr = explode(',', $ids);

        $is_pasteArr = [1=>'是',2=>'否','3'=>'仓库需换码'];

        if(!empty($ids)){
            $params['id']   = $ids_arr;
        }else{
            $params = [
                'sku' => $this->input->get_post('sku'), // SKU
                'purchase_number' =>$this->input->get_post('purchase_number'),
                'demand_status' => $this->input->get_post('demand_status'),
                'supplier_code' => $this->input->get_post('supplier_code'),
                'is_create' => $this->input->get_post('is_create'),//是否生成标签(1是，2否)
                'is_update' => $this->input->get_post('is_update'),//是否更新 1是2否
                'is_wrong' =>$this->input->get_post('is_wrong'),//是否获取失败(2.否,1.是)
                'shipment_type' =>$this->input->get_post('shipment_type'),//发运类型(1.工厂发运;2.中转仓发运)
                'purchase_is_download' => $this->input->get_post('purchase_is_download'),//采购是否已下载1是2否
                'supplier_is_download' => $this->input->get_post('supplier_is_download'),//供应商是否已下载
                'enable' => $this->input->get_post('enable'),//是否启用门户系统 1禁用2启用
                'is_dispose' =>$this->input->get_post('is_dispose'),//是否处理1否2是
                'order_time_start' => $this->input->get_post('order_time_start'),//下单时间
                'order_time_end' => $this->input->get_post('order_time_end'),
                'update_time_start' => $this->input->get_post('update_time_start'),//最近一次更新时间
                'update_time_end' => $this->input->get_post('update_time_end'),
                'is_promise' => $this->input->get_post('is_promise'),
                'is_plan'    =>  $this->input->get_post('is_plan'),
                'compact_number'=>$this->input->get_post('compact_number'),
                'new_des_warehouse'=>$this->input->get_post('new_des_warehouse'),
                'is_warehouse_update'=>$this->input->get_post('is_warehouse_update'),


            ];
        }
        $demand_info = $this->purchase_label_model->get_combine_list($params,0,9500,1,true);
        $purchase_tax_list_export = $demand_info['data_list'];

        /*      $this->load->model('warehouse/Logistics_type_model');
              $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
              $logistics_type_list = array_column($logistics_type_list,'type_name','type_code');*/

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');

        /*      $this->load->model('system/Reason_config_model');
              $param['status'] = 1;//启用的
              $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
              $category_list = array_column($cancel_reason_category_list['values'],'reason_name','id');
              $skus = array_column( $demand_info['data_list'],"sku");*/
        //$tax_list_tmp = [];
        $order_status_list = getPurchaseStatus();

        if($purchase_tax_list_export){
            foreach($purchase_tax_list_export as $value){

                $value_temp = [];
                $order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);//采购单信息
                $order_item_info = $this->purchase_order_items_model->get_item($value['purchase_number'], $value['sku'],true);
                $suggest_info = $this->purchase_suggest_model->get_one(0,$value['demand_number']);
                $status_name = isset($order_status_list[$order_info['purchase_order_status']])?$order_status_list[$order_info['purchase_order_status']]:NULL;
                $demand_status_name=isset($order_status_list[$suggest_info['suggest_order_status']])?$order_status_list[$suggest_info['suggest_order_status']]:NULL;
                $value_temp['status_name'] = $status_name;//订单状态
                $value_temp['demand_status_name'] = $demand_status_name;//备货单状态
                $value_temp['demand_number'] = $value['demand_number'];
                $value_temp['sku'] = $value['sku'];
                $value_temp['purchase_number'] = $value['purchase_number'];//采购单号
                $value_temp['compact_number'] = $value['compact_number'];//采购单号

                $value_temp['supplier_name'] = $value['supplier_name'];
                $value_temp['num'] = empty($order_item_info['confirm_amount']) ? 0 : $order_item_info['confirm_amount'];//采购数量
                $value_temp['is_drawback'] = $order_info['is_drawback'] == 1 ? '退税' : '否';//是否退税
                $value_temp['is_create'] = !empty($value['combine_label']) ? '是' : '否';
                $value_temp['content'] = $value['combine_label'];
                $value_temp['is_unique'] = $value['is_unique'] == 1 ? '是' : '否';//条码是否唯一
                $value_temp['error_mes'] = $value['error_mes'];

                $value_temp['purchase_down'] = $value['purchase_barcode_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';
                $value_temp['supplier_down'] = $value['supplier_down_time'] != '0000-00-00 00:00:00' ? '是' : '否';

                $value_temp['create_time'] = $value['create_time']; //仓库推送时间
                $value_temp['order_time'] = $value['order_time'];// 下单时间
                $barcode_is_update = '';
                if ($value['combine_is_update'] == 1) {
                    $barcode_is_update .= '是';
                    if ($value['combine_is_update'] == 1) {
                        $barcode_is_update .= '\n' . '已处理';
                    } else {
                        $barcode_is_update .= '\n' . '未处理';

                    }
                } else {
                    $barcode_is_update .= '否';

                }
                $value_temp['is_update'] = $barcode_is_update;
                $destination_warehouse_info = $this->purchase_order_model->purchase_db->select('destination_warehouse_code')->from('shipment_track_list')->where('shipment_type',$suggest_info['shipment_type'])->where('demand_number',$value['demand_number'])->get()->row_array();        //查询是否是发运系统物流跟踪仓库，如果是，显示

                if (!empty($destination_warehouse_info)) {
                    $destination_warehouse = $destination_warehouse_info['destination_warehouse_code'];

                } else {
                    $destination_warehouse = $value['destination_warehouse'];

                }

                $value_temp['destination_warehouse'] = isset($warehouse_list[$destination_warehouse]) ? $warehouse_list[$destination_warehouse] : '';
                $value_temp['new_des_warehouse'] = isset($value['new_des_warehouse']) ? $warehouse_list[$value['new_des_warehouse']] : '';
                $value_temp['is_warehouse_update'] = $value['new_des_warehouse']==$destination_warehouse?'否':'是';
                $value_temp['enable'] = $value['enable'] == 1 ? '是' : '否';
                $value_temp['shipment_type'] = $order_info['shipment_type'] == 1 ? '工厂发运' : '中转仓发运';
                $is_paste =$is_pasteArr[$value['is_paste']];
                $value_temp['is_paste'] = $is_paste . '\n' . $value['remark'];
                $value_temp['is_plan'] = $value['source_from'] == 1 ? '是' : '否';

                $tax_list_tmp[] = $value_temp;


            }
        }
        $this->success_json($tax_list_tmp);
    }






    /*
     * 推送备货单到仓库获取反馈信息
     * /purchase/purchase_label/send_wms_label
     */

    public function send_wms_combine_label(){
        $msg = '推送完成';//推送信息
        $error_msg = '';
        $demand_number = $this->input->get_post('demand_number');
        if(!is_array($demand_number)&&count($demand_number)==0) $this->error_json('数据有误');
        $purchase_arr = array();
        $supplier_code_arr =array();
        foreach ($demand_number as $demand_no) {
            $demand_info = $this->purchase_label_model->get_one($demand_no);
            if (empty($demand_info)) $this->error_json('备货单:'.$demand_no.'不存在');
            $purchase_arr[$demand_info['purchase_number']][] =$demand_info;
            $supplier_code_arr[$demand_info['purchase_number']] = $demand_info['supplier_code'];

        }

//已更新备货单号:
        $send_data = [];//发送数据
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



            if ($trans_res['code']!=200) $this->error_json('采购单号:'.$po.'供应商信息缺失');

            //临时调整，后续换回来
            $temp_supplier_arr = ['A1448676093'];
            if (in_array($supplier_code,$temp_supplier_arr)) {
                $trans_res['data']['contactAddress'] ='Tangnan Industrial Development Zone, Songgang, Shishan Town, Nanhai District,Foshan City';

            }

            $supplier_info =  $this->purchase_order_model->purchase_db
                ->select('*')
                ->where('supplier_code',$supplier_code)
                ->get('supplier')
                ->row_array();



            if (empty($supplier_info)) $this->error_json('采购单号:'.$po.'供应商信息缺失');

            foreach ($demand_info as $demand) {
                //拼接请求信息
                $order_item_info = $this->purchase_order_items_model->get_item($demand['purchase_number'], $demand['sku'],true);//采购数量
                $num = $order_item_info['confirm_amount'];
                $suggest_info = $this->purchase_suggest_model->get_one(0,$demand['demand_number']);
                if (empty($suggest_info)) {
                    $error_msg.=$demand['demand_number'].'获取失败,失败原因:备货单已删除<br/>';
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

        //$wms_url= 'http://dp.yibai-it.com:33335/Api/Transit/Index/createTransitPdfLabel';
		   $send_res = getCurlData($wms_url,$post_data);//翻译结果



        if (!empty($send_res)) {
            //更新成功
            $send_res = json_decode($send_res,true);



            if ($send_res['status'] == 1) {//推送成功

                if (is_array($send_res['success_list'])&&count($send_res['success_list'])>0) {
                    foreach($send_res['success_list'] as $success){
                        $update_data = array();
                        $su_item = $this->purchase_label_model->get_label_item($success['purchase_order_no'],$success['sku']);

                        if ($su_item){//更新物流标签信息

                            // $is_ok = false;
                            /*   if (($su_item['purchase_label_down_time']=='0000-00-00 00:00:00')&&($su_item['supplier_down_time']=='0000-00-00 00:00:00'))
                               {
                                   $is_ok = true;
                               } elseif (($su_item['purchase_label_down_time']!='0000-00-00 00:00:00')||($su_item['supplier_down_time']!='0000-00-00 00:00:00')) {
                                   if (strtotime($success['update_time']>strtotime($su_item['update_time']))) {
                                       $is_ok = true;

                                   }

                               } elseif (($su_item['purchase_barcode_down_time']!='0000-00-00 00:00:00')||($su_item['supplier_down_time']!='0000-00-00 00:00:00')) {
                                   if (strtotime($success['update_time'] > strtotime($su_item['update_time']))) {
                                       $is_ok = true;

                                   }
                               }*/

                            /* if ($is_ok) {*/
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

                if (is_array($send_res['fail_list'])&&count($send_res['fail_list'])>0) {
                    foreach ($send_res['fail_list'] as $fail) {
                        $update_data = array();
                        $update_data['combine_error_msg'] = $fail['message'];
                        $error_msg.=$fail['purchase_order_no'].'_'.$fail['sku'].'获取失败,失败原因:'.$fail['message'].'<br/>';
                        $this->purchase_order_model->purchase_db->update('purchase_label_info',$update_data
                            , array('purchase_number'=>$fail['purchase_order_no'],'sku'=>$fail['sku']));

                    }

                }

            }else{//推送失败
                $this->error_json($send_res['msg']);

            }


        } else {
            $this->error_json('请求仓库接口没有响应');
        }


        $this->success_json($msg.$error_msg);



    }











}











