<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Order_tax extends MY_API_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('purchase/purchase_order_tax_model');    
    }
    
    /**
     * 推送财务汇总系统
     * www.cg.com/order_tax/get_purchase_finance
     */
    public function get_purchase_finance(){
         set_time_limit(0);
        try {
            $order = $this->purchase_order_tax_model->get_push_finance();
            if (empty($order)) {
                throw new Exception('没有可用数据');
            }
            //组装数据
            $data = [];
            foreach ($order as  $value) {
                $temp['pur_number'] = $value['purchase_number'];
                $temp['sku'] = $value['sku'];
                $temp['declare_name'] = $value['product_name'];
                $temp['supplier_code'] = $value['supplier_code'];
                $temp['supplier_name'] = $value['supplier_name'];
                $temp['tickets_number'] = $value['uumber_invoices'];//开票数量
                $temp['invoice_amount'] = $value['invoiced_amount'];//已开票金额
                $temp['ticketed_point'] = 0.13;
                $temp['price'] = $value['unit_price'];
                $temp['invoice_code'] = $value['invoice_code_right'];
                $data[] = $temp;
                unset($temp);
            }

            $url = getConfigItemByName('api_config', 'declare_customs', 'purchaseSysInsertData');
            $batch_data = [
                'batch_data' => json_encode($data),
            ];
            $reslut = getCurlData($url, $batch_data);
            $api_log = [
                'record_number' => 'purchaseSysInsertData',
                'api_url' => $url,
                'record_type' => '采购系统推送财务系统',
                'post_content' => json_encode($data),
                'response_content' => $reslut,
                'create_time' => date('Y-m-d H:i:s')
            ];
            $resluts = json_decode($reslut, TRUE);
            $errorMess = '';
            if ($resluts['status'] == 1) {
                $this->db->insert('api_request_log',$api_log); 
                unset($api_log);
                if (isset($resluts['data']) && !empty($resluts['data'])) {
                    foreach ($resluts['data'] as $val) {
                        if ($val['status'] == 1) { //成功      
                            $this->purchase_order_tax_model->get_push_finance_status($val['pur_number'], $val['sku']);
                        } elseif ($val['status'] == 0) {
                            $errorMess .= $val['pur_number'] . '-' . $val['sku'] . $val['errorMess'] . "<br>";
                        } else {
                            throw new Exception('财务系统返回参数异常');
                        }
                    }
                }
                if (empty($errorMess)) {
                    echo "推送成功";
                } else {
                    throw new Exception($errorMess);
                }
            } else {
                throw new Exception('没有请求到财务系统接口');
            }
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }

}