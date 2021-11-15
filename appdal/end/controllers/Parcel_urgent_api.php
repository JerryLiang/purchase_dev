<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 包裹加急推送到仓库
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Parcel_urgent_api extends MY_API_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse/parcel_urgent_model');
    }

    /**
     * 包裹加急推送到仓库
     /parcel_urgent_api/push_logistics_info
     * @author Jaden
     */
    public function push_logistics_info(){
        $logistics_num = $this->input->get_post('logistics_num');
        $purchase_number = $this->input->get_post('purchase_number');
        $where="p.push_status=0 and p.is_deleted=1 and p.logistics_num<>'' and (o.is_push=1 or o.push_to_wms=2)";

        if (!empty($purchase_number)){
            $where.=' AND p.purchase_order_num="'.$purchase_number.'"';
        }

        if (!empty($logistics_num)){
            $where.=' AND p.logistics_num="'.$logistics_num.'"';
        }

        $push_list = $this->db->select('p.id,p.logistics_num,p.purchase_order_num')
                    ->from($this->parcel_urgent_model->tableName().' p')
                    ->join('purchase_order o', 'o.purchase_number=p.purchase_order_num', 'left')
                    ->where($where)
                    ->order_by('p.id desc')
                    ->limit(100)
                    ->get()
                    ->result_array();

        if(empty($push_list)) {
            var_dump('没有数据要推送');exit;
        }
        //读取配置文件参数，获取推送地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('logistics'))) {
            $warehouse_data_info = $this->config->item('logistics');
            $_url_ip = isset($warehouse_data_info['ip'])?$warehouse_data_info['ip']:'';
            $_url_push_void = isset($warehouse_data_info['pust_logistics'])?$warehouse_data_info['pust_logistics']:'';
            if(empty($_url_ip) or empty($_url_push_void)){
                exit('推送地址缺失');
            }
            $url = $_url_push_void;
        }
        if(empty($url)){
           exit('推送地址缺失'); 
        }
        $pust_data['logistics_num_info'] = json_encode($push_list);
        $pust_data['token'] = json_encode(stockAuth());
        $response = getCurlData($url,$pust_data);
        $res = json_decode($response,1);
        if(is_array($res) && !empty($res)) {
          
                $successed_ids = [];
                foreach($res['success_list'] as $k=>$v) {
                        $successed_ids[] = $k;
                }

                $fail_data = [];
                foreach($res['fail_list'] as $k=>$v) {
                    if($v['status'] == 'fail') {
                        $fail_data[$k]['id'] = $k;
                        $fail_data[$k]['msg'] = $v['msg'];
                    }
                }
            if(!empty($successed_ids) || !empty($fail_data)) {
                //处理推送成功数据
                if(!empty($successed_ids)){
                    $successed_res_str = implode(',', $successed_ids);
                    $where = 'id in('.$successed_res_str.')';
                    $update_data['push_status'] = 1;
                    $update_data['push_res'] = '推送成功';
                    $successed_res = $this->parcel_urgent_model->update_logistics($where,$update_data);

                }
                //处理推送失败数据
                if(!empty($fail_data)){
                    foreach ($fail_data as $key => $value) {
                        $id_where = 'id="'.$value['id'].'"';
                        $id_update_data['push_status'] = 3;
                        $id_update_data['push_res'] = !empty($value['msg'])?$value['msg']:'推送失败';
                        $this->parcel_urgent_model->update_logistics($id_where,$id_update_data);
                    }
                }
                $successed_res = !empty($successed_res)?$successed_res:0;
                var_dump('推送成功的条数：'.$successed_res.'-----推送失败的条数：'.count($fail_data));
                
            } else {
                var_dump($res);
            }
        } else {
            var_dump($res);
        }
    }

    /**
     * 包裹加急推送到仓库(已推送失败的且非已加急的)
    /parcel_urgent_api/push_logistics_info
     * @author Jaden
     */
    public function push_logistics_info_fail_list(){
        $logistics_num = $this->input->get_post('logistics_num');
        $purchase_number = $this->input->get_post('purchase_number');
        $where="p.push_status=3 and p.push_res!='此条数据已经加急了' and p.is_deleted=1 and p.logistics_num<>''";

        if (!empty($purchase_number)){
            $where.=' AND p.purchase_order_num="'.$purchase_number.'"';
        }

        if (!empty($logistics_num)){
            $where.=' AND p.logistics_num="'.$logistics_num.'"';
        }

        $push_list = $this->db->select('p.id,p.logistics_num,p.purchase_order_num')
            ->from($this->parcel_urgent_model->tableName().' p')
            ->join('purchase_order o', 'o.purchase_number=p.purchase_order_num', 'left')
            ->where($where)
            ->order_by('p.id desc')
            ->limit(300)
            ->get()
            ->result_array();
        if(empty($push_list)) {
            var_dump('没有数据要推送');exit;
        }
        //读取配置文件参数，获取推送地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('logistics'))) {
            $warehouse_data_info = $this->config->item('logistics');
            $_url_ip = isset($warehouse_data_info['ip'])?$warehouse_data_info['ip']:'';
            $_url_push_void = isset($warehouse_data_info['pust_logistics'])?$warehouse_data_info['pust_logistics']:'';
            if(empty($_url_ip) or empty($_url_push_void)){
                exit('推送地址缺失');
            }
            $url = $_url_push_void;
        }
        if(empty($url)){
            exit('推送地址缺失');
        }
        $pust_data['logistics_num_info'] = json_encode($push_list);
        $pust_data['token'] = json_encode(stockAuth());
        $response = getCurlData($url,$pust_data);
        $res = json_decode($response,1);
        if(is_array($res) && !empty($res)) {

            $successed_ids = [];
            foreach($res['success_list'] as $k=>$v) {
                $successed_ids[] = $k;
            }

            $fail_data = [];
            foreach($res['fail_list'] as $k=>$v) {
                if($v['status'] == 'fail') {
                    $fail_data[$k]['id'] = $k;
                    $fail_data[$k]['msg'] = $v['msg'];
                }
            }
            if(!empty($successed_ids) || !empty($fail_data)) {
                //处理推送成功数据
                if(!empty($successed_ids)){
                    $successed_res_str = implode(',', $successed_ids);
                    $where = 'id in('.$successed_res_str.')';
                    $update_data['push_status'] = 1;
                    $update_data['push_res'] = '推送成功';
                    $successed_res = $this->parcel_urgent_model->update_logistics($where,$update_data);

                }
                //处理推送失败数据
                if(!empty($fail_data)){
                    foreach ($fail_data as $key => $value) {
                        $id_where = 'id="'.$value['id'].'"';
                        $id_update_data['push_status'] = 3;
                        $id_update_data['push_res'] = !empty($value['msg'])?$value['msg']:'推送失败';
                        $this->parcel_urgent_model->update_logistics($id_where,$id_update_data);
                    }
                }
                $successed_res = !empty($successed_res)?$successed_res:0;
                var_dump('推送成功的条数：'.$successed_res.'-----推送失败的条数：'.count($fail_data));

            } else {
                var_dump($res);
            }
        } else {
            var_dump($res);
        }
    }


}