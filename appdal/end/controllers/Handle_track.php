<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";


class Handle_track extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Logistics_info_model');
        $this->load->model('handle_track_model');
    }

    /**
     * 手动获取物流信息
     * handle_track/get_track_kdn?express_no=0000000&carrier_code=aaaaaa
     */
    public function get_track_kdn()
    {
        $express_no = $this->input->get_post("express_no");
        $carrier_code = $this->input->get_post("carrier_code");
        $customer_name = $this->input->get_post("customer_name");
        $data = $this->Logistics_info_model->get_track_by_kdbird($express_no, $carrier_code, $customer_name);
        if(is_array($data))$data = json_encode($data);
        exit($data);
    }

    /**
     * 更新物流单状态
     */
    public function update_track_status()
    {
        $express_no = $this->input->get_post("express_no");
        $status = $this->input->get_post("status");
        $data = $this->handle_track_model->update_track_status($express_no, $status);
        if(is_array($data))$data = json_encode($data);
        exit($data);
    }

    public function update_status()
    {
        $header = array('Content-Type: application/json');
        $start = $this->input->get_post("start");
        if(empty($start))$start = 10;
        $start = (int)$start;
        for ($i=$start;$i<180;$i++){
            $start = date("Y-m-d", strtotime(' -'.$i.' days'));
            $data = $this->handle_track_model->get_list($start);
            if($data && !empty($data)){
                $x = 1;
                foreach ($data as $val){
                    $x ++;
                    $exno = trim($val['express_no']);
                    $code = $val['carrier_code'];

                    if($exno == '' || !preg_match('/^[0-9_a-zA-Z\-]{7,24}$/i', $exno)) {
                        echo $x."...gs...err\n";
                        continue;
                    }

                    // 获取数据
                    $StateEx = false;
                    $res = $this->Logistics_info_model->get_track_by_kdbird($exno, $code, '');
                    if(!is_array($res))$res = json_decode($res, true);
                    if(isset($res['Success']) && $res['Success'] === true){
                        $StateEx = $this->Logistics_info_model->switch_status($res['StateEx'], 1);
                    }

                    // 更新
                    if($StateEx !== false && $StateEx > 0){
                        $url = "http://pms.yibainetwork.com:81/handle_track/update_track_status?express_no={$exno}&status={$StateEx}";
                        $req = getCurlData($url, [], 'get', $header);
                        echo "{$start}...".$exno."...status:{$StateEx}...".$req."\n";
                    }
                }
            }else{
                echo $start."not data\n";
            }
            sleep(1);
        }
    }

}