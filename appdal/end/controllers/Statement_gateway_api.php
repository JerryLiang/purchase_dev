<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

class Statement_gateway_api extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('statement/Purchase_statement_model');

    }

    /**
     * 获取用于生成对账单pdf的html数据
     */
    public function get_statement_pdf_html(){
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number)) $this->error_json('参数错误');
        try{
            $statement = $this->Purchase_statement_model->get_statement($statement_number);
            if($statement){
                $key             = "print_statement_tmp";//缓存键
                $print_statement = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_statement_new');
                $print_statement .= '?statement_number='.$statement_number;

                $header         = array('Content-Type: application/json');
                $html           = getCurlData($print_statement,json_encode($statement, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果
                $this->success_json(['html' => $html,'key' => $key.'-'.$statement_number]);
            }else{
                $this->error_json('未获取到数据');
            }
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 推送对账单数据到门户系统
     * /statement_gateway_api/push_statement_to_gateway
     * @author Justin
     */
    public function push_statement_to_gateway()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $result = $this->Purchase_statement_model->push_statement_to_gateway_cron();
        if ($result['code']) {
            //推送成功
            echo implode('<br>', $result['data']);
        } else {
            //推送失败
            exit($result['msg']);
        }
    }

    /**
     * 推送对账单付款状态到门户系统
     * /statement_gateway_api/push_pay_status_to_gateway
     * @author Justin
     */
    public function push_pay_status_to_gateway()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $result = $this->Purchase_statement_model->push_pay_status_to_gateway_cron();
        if ($result['code']) {
            //推送成功
            echo implode('<br>', $result['data']);
        } else {
            //推送失败
            exit($result['msg']);
        }
    }
}