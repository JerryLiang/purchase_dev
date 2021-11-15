<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 入库报关详情
 * User: Jaden
 * Date: 2019/02/19 0027 11:17
 */

class Declare_customs_api extends MY_API_Controller{
    protected $data_abnormal_check_key = 'INVOICE_IS_ABNORMAL';//开票异常状态

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase/Declare_customs_model','customs_model');
        $this->load->model('purchase/Purchase_order_items_model','order_items_model');
        $this->load->model('product/Product_model','product_model');
        $this->load->model('Purchase_order_cancel_model','m_cancel',false,'purchase');
    }

    /**
    * 接收报关详情
    * /declare_customs_api/get_declare_customs_list
    */
    public function get_declare_customs_list(){
    	if(isset($_REQUEST['purFba']) and $_REQUEST['purFba']){// FAB报关单
            $purFba = $_REQUEST['purFba'];
        }elseif(isset($_REQUEST['purHwc']) and $_REQUEST['purHwc']){// 海外仓报关单
            $purFba = $_REQUEST['purHwc'];
        }
        $response = ['success_list'=>[], 'failure_list'=>[]];
        if(isset($purFba) && !empty($purFba))
        {   
            $customs_list = json_decode($purFba,true);
            $insert_data = array();
            $customs_quantity_num = 0;
            $all_customs_quantity_num = 0;

            //写入API请求记录表
            apiRequestLogInsert(
                [
                    'record_number' => 'RECEIVE_LOGISTICS_DECLARE_CUSTOMS',
                    'record_type' => 'RECEIVE_LOGISTICS_DECLARE_CUSTOMS',
                    'api_url' => '/declare_customs_api/get_declare_customs_list',
                    'post_content' => $purFba,
                    'response_content' => ''
                ],
                'api_request_log'
            );

            if(empty($customs_list))echo json_encode(['text' => '没有获取到数据！']);exit;
            foreach ($customs_list as $key => $value) {
                if(isset($value['detail']) and $value['detail']){
                    foreach ($value['detail'] as $dk => $dv) {
                        $insert_data['purchase_number'] = !empty($dv['pur_number'])?$dv['pur_number']:'';
                        $insert_data['sku'] = !empty($dv['sku'])?$dv['sku']:'';
                        $insert_data['demand_number'] = !empty($dv['demand_number'])?$dv['demand_number']:'';
                        $insert_data['customs_number'] = !empty($value['custom_number'])?$value['custom_number']:'';
                        $insert_data['customs_name'] = !empty($dv['declare_name'])?$dv['declare_name']:'';
                        $insert_data['customs_unit'] = !empty($dv['declare_unit'])?$dv['declare_unit']:'';
                        $insert_data['customs_quantity'] = !empty($dv['amounts'])?$dv['amounts']:'';
                        $insert_data['unit_price'] = !empty($dv['price'])?$dv['price']:'';
                        $insert_data['customs_type'] = !empty($dv['declare'])?$dv['declare']:'';
                        $insert_data['customs_time'] = !empty($value['clear_time'])?$value['clear_time']:'';
                        $insert_data['create_time'] = date('Y-m-d H:i:s');
                        $insert_data['key_id'] = !empty($dv['detail_id'])?$dv['detail_id']:'';;
                        $insert_data['order_id'] = !empty($value['order_id'])?$value['order_id']:'';
                        $insert_data['is_clear'] = !empty($value['is_clear'])?$value['is_clear']:'';
                        //检测是否存在数据
                        $where = 'key_id="'.$dv['detail_id'].'" AND sku="'.$dv['sku'].'" AND purchase_number="'.$dv['pur_number'].'"';
                        $customs_info = $this->customs_model->getInvoiceByWhere($where);
                        if(empty($customs_info)){
                            $status = $this->db->insert($this->customs_model->tableName(), $insert_data);
                        }else{
                            $this->db->where($where);
                            $status = $this->db->update($this->customs_model->tableName(), $insert_data);
                        }
                        //改变采购单表的报关状态
                        //查找现有报关数量
                        $de_info = $this->customs_model->getInvoiceByWherelist(array($dv['pur_number']));
                        if(!empty($de_info) and isset($de_info[$dv['pur_number'].'_'.$dv['sku']])){
                            $customs_quantity_num = $de_info[$dv['pur_number'].'_'.$dv['sku']];    
                        }else{
                            $customs_quantity_num = 0;
                        }
                        $customs_quantity_num = $customs_quantity_num;
                        $all_customs_quantity_num = $customs_quantity_num;
                        //采购单入库数量
                        $order_items_info = $this->order_items_model->get_item($dv['pur_number'],$dv['sku'],true);
                        if(!empty($order_items_info) AND isset($order_items_info['upselft_amount'])){
                            $upselft_amount = $order_items_info['upselft_amount'];
                        }else{
                            $upselft_amount = 0; 
                        }

                        //更新开票是否异常状态
                        $this->rediss->set_sadd($this->data_abnormal_check_key,sprintf('%s$$%s',$dv['pur_number'],$dv['sku']));
                        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$this->data_abnormal_check_key);

                        if($upselft_amount==$all_customs_quantity_num){
                            $customs_status = CUSTOMS_DECLARATION;//已报关
                        }elseif ($upselft_amount>$all_customs_quantity_num) {
                            $customs_status = PARTIAL_DECLARATION;//部分报关
                        }else{
                            $customs_status = 4;//报关数据大于入库数量
                        }

                        //是否异常   报关名称=开票品名(出口申报中文名) 为是，反之则为否
                        //根据SKU查开票品名
                        $product_info = $this->product_model->get_product_info($dv['sku']);
                        if(!empty($product_info) && isset($product_info['declare_cname'])){
                            $declare_cname = $product_info['declare_cname'];
                        }else{
                            $declare_cname = '';
                        }

                        if($declare_cname == $dv['declare_name']){
                            $is_abnormal = IS_ABNORMAL_FALSE;
                        }else{
                            $is_abnormal = IS_ABNORMAL_TRUE;
                        }
                        //$this->order_items_model->update_item_customs_status($dv['pur_number'],$dv['sku'],$customs_status);
                        $this->db->where('purchase_number', $dv['pur_number'])->where('sku',$dv['sku'])->update('purchase_order_items', array('customs_status'=>$customs_status,'is_abnormal'=>$is_abnormal));

                        if($status) {
                            $response['success_list'][] = $value['id'];
                        } else {
                            $response['failure_list'][] = $value['id'];
                        }
                    }
                }else{
                    $response['failure_list'][] = $value['id'];
                }    
            }
            
            $success_list = array_unique($response['success_list']);
            $failure_list = array_unique($response['failure_list']);
            echo json_encode(['success_list'=> $success_list, 'failure_list'=> $failure_list]);exit;
        } else {
            echo json_encode(['text' => '没有任何的数据过来！']);exit;
        }
    }


}