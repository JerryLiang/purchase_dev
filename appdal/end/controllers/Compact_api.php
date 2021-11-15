<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Compact_api extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('compact/Compact_list_model', 'compact_model');
    }

    public function push_compact_tel()
    {
        $compact_number = $this->input->get_post('compact_number');
        if (empty($compact_number)) {
            $this->error_json('缺少合同编号');
        }
        $this->load->helper('status_order');
        try {
            $result = $this->compact_model->get_print_compact_data($compact_number);
            if ($result) {
                $result['compact_data']['is_freight'] = getFreightPayment($result['compact_data']['is_freight']);// 运费支付方
                $result['compact_data']['total_price'] = format_price($result['compact_data']['total_price']);

                $data = json_encode($result);
                if ($result['compact_data']['is_drawback'] == 1) {
                    $key = "tax_compact";//缓存键
                    $css_file_name = 'taxRefundTemplate.css';
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'tax_print_compact');
                } else {
                    $key = "ntax_compact";//缓存键
                    $css_file_name = 'nonRefundableTemplate.css';
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'no_tax_print_compact');
                }
                $print_compact .= '?compact_number=' . $compact_number;
                $this->rediss->setData($key . '-' . $compact_number, $data);
                $html = getCurlData($print_compact, '', 'get');//file_get_contents($print_compact);
                $css1 = CG_SYSTEM_WEB_FRONT_IP."front/print_template/mycss/".$css_file_name;
                $style1 = file_get_contents($css1);
                $html = str_replace('<body>','<style>'.$style1.'</style><body>',$html);


                $this->success_json(['html' => $html, 'key' => $key]);
            } else {
                $this->error_json('未获取到数据');
            }
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }
}