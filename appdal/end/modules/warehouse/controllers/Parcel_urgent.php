<?php
/**
 * Created by PhpStorm.
 * 包裹加急控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Parcel_urgent extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('parcel_urgent_model');
    }

    /**
     * 包裹加急
     /warehouse/parcel_urgent/logistics_urgent_list
     * @author Jaden
     */
    public function logistics_urgent_list(){
        $this->load->helper('status_product');
        $params = [
            'logistics_num' => $this->input->get_post('logistics_num'), 
            'push_status' => $this->input->get_post('push_status'),
            'order_by_time' => $this->input->get_post('order_by_time'),
            'create_id' => $this->input->get_post('create_id'),//导入人
        ]; 
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $field = 'a.id,a.logistics_num,a.purchase_order_num,a.create_name,a.create_time,a.update_time,a.push_status,a.push_res';
        $orders_info = $this->parcel_urgent_model->get_logistics_urgent_list($params, $offset, $limit,$field);
        $orders_info['key'] = array('ID','物流单号','快递公司','采购单号','导入人','导入时间','更新时间','是否推送到仓库','推送结果','操作');
        //采购员
        
        $drop_down_box['is_push_list'] = getParcelUrgentState();

        $this->load->model('user/purchase_user_model');
        $data_list = $this->purchase_user_model->get_user_all_list();
        $data_list = array_column($data_list,'name','id');
        $data_list = ['0' => '空'] + $data_list;

        $drop_down_box['user_all_list'] = $data_list;

        $orders_info['drop_down_box'] = $drop_down_box;
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        $page_data = $orders_info['page_data'];
        unset($orders_info['page_data']);

        $this->success_json($orders_info,$page_data);
    }

    /**
     * 包裹加急导入
     /warehouse/parcel_urgent/logistics_urgent_import
     * @author Jaden
     */
    public function logistics_urgent_import(){
        $import_json = $this->input->get_post('import_arr');
        $result_list = json_decode($import_json,true);
        $errorMsg = '';
        $n = count($result_list);
        $nn = 0;
        $j = 0;
        $i = 0;
        $logistics_arr = array();
        foreach ($result_list as $key => $value) {
            if($key==0){
                continue;
            }
            $logistics_num =$value[0];//物流单号
            $purchase_order_num = $value[1];//采购单号

            if( empty($logistics_num) || empty($purchase_order_num) ){
                $j++;
                continue;
            }
            //检测数据是否存在
            $parcel_info = $this->parcel_urgent_model->get_parcel_urgent_info($logistics_num,$purchase_order_num);
            if(!empty($parcel_info)){
                $nn++;
                continue;
            }
            $i++;

            $add_arr['logistics_num'] = $logistics_num;
            $add_arr['purchase_order_num'] = $purchase_order_num;
            $add_arr['create_id'] = getActiveUserId();
            $add_arr['create_name'] = getActiveUserName();
            $add_arr['create_time'] = date('Y-m-d H:i:s');
            $add_arr['update_time'] = date('Y-m-d H:i:s');
            array_push($logistics_arr,$add_arr);
        }
        $result = $this->parcel_urgent_model->insert_parcel_batch_all($logistics_arr);
        if($result){
            $not_num = $j+$nn;
            $msg = '共'.($n-1).'条记录，导入成功'.$i.'条数据,导入失败'.$not_num.'条数据！';
            $this->success_json($msg);
        }else{
            $this->error_data_json('导入失败,数据是否重复！');
        }
    }


    /**
     * 删除数据
     * /warehouse/parcel_urgent/logistics_delete
     * @author Jaden 2019-1-17
    */
    public function logistics_delete(){
        $ids = $this->input->get_post('ids');
        if(empty($ids)){
            $this->error_data_json('请传参数');
        }
        $ids_arr = explode(',', $ids);
        if(!is_array($ids_arr)){
            $this->error_data_json('参数有误');
        }
        $this->db->where_in('id', $ids_arr);
        $update_status = $this->db->update($this->parcel_urgent_model->tableName(), array('is_deleted'=>0));
        if($update_status){
            $this->success_json('删除成功');
        }else{
            $this->error_data_json('删除失败，请稍后再试');
        }
    }


    /**
     * 手动推送
     * /warehouse/parcel_urgent/push_logistics_data
     * @author Jaden 2019-1-17
    */
    public function push_logistics_data(){
        $ids = $this->input->get_post('ids');
        if(!empty($ids)) {
            $ids = explode(',',$ids);
        }else{
            $this->error_json('参数错误');
        }

        $shortage_list = $this->parcel_urgent_model->get_push_logistics_urgent_list($ids);

        if(empty($shortage_list)){
            $this->error_json('暂无数据可推送');    
        }

        foreach ($shortage_list as $key => $value){
            if (empty($value['logistics_num'])) $this->error_json('存在物流单号为空的数据');
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
            $this->error_json('推送地址缺失');
        }
        $pust_data['logistics_num_info'] = json_encode($shortage_list);      
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
               $res = '推送成功的条数：'.$successed_res.'-----推送失败的条数：'.count($fail_data);
                
            } else {
                $res = $res;
            }
        } else {
            $this->error_json('推送失败');
            $res = $res;
        }
        $this->success_json($res);
    }

    /**
     * 包裹加急导出
    /product/product/product_export
     * @author Jaden
     */
    public function logistics_urgent_export(){
        ini_set('memory_limit','3000M');
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->load->helper('status_product');
        $ids = $this->input->get_post('ids');

        if(!empty($ids)){
            $params['ids']   = $ids;
        }else{
            $params = [
                'logistics_num' => $this->input->get_post('logistics_num'),//物流单号
                'push_status' => $this->input->get_post('push_status'),//是否推送到仓库
                'create_id' => $this->input->get_post('create_id'),//导入人
            ];
        }
        $page = $this->input->get_post('offset');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = 1;
        $offset = ($page - 1) * $limit;
        $field = 'a.id,a.logistics_num,a.purchase_order_num,a.create_name,a.create_time,a.update_time,a.push_status,a.push_res';
        $data_info = $this->parcel_urgent_model->get_logistics_urgent_list($params, $offset, $limit,$field,true);

        $total = $data_info['page_data']['total'];

        if($total>100000){//单次导出限制
            $template_file = 'product.xlsx';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }
        //$total = 1000;
        //前端路径
        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $file_name = 'logistics_urgent_list_'.date('YmdH_i_s').'.csv';
        $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $fp = fopen($product_file, "a");
        $heads = ['物流单号','采购单号','导入人','导入时间','更新时间','是否推送到仓库','推送结果'];
        foreach($heads as $key => $item) {
            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);
        if($total>=1){
            $page_limit = 10000;
            for ($i=1; $i <=ceil($total/$page_limit) ; $i++) {
                $export_offset = ($i - 1) * $page_limit;
                $data_info = $this->parcel_urgent_model->get_logistics_urgent_list($params, $export_offset, $page_limit,$field,true);

                $logistics_urgent_list = $data_info['value'];

                if($logistics_urgent_list){
                    foreach($logistics_urgent_list as $key=>$v_value){

                        $v_value_tmp                       = [];
                        $v_value_tmp['logistics_num'] = '';
                        if (isset($v_value['logistics_info'])&&!empty($v_value['logistics_info'])){
                            foreach ($v_value['logistics_info'] as $k => $v){
                                $v_value_tmp['logistics_num'] .= sprintf('%s-%s ',$v['cargo_company_id']??'',$v['express_no']??'');
                            }
                        }
                        $v_value_tmp['logistics_num'] =  iconv("UTF-8", "GBK//IGNORE", $v_value_tmp['logistics_num']);

                        $v_value_tmp['purchase_order_num'] = iconv("UTF-8", "GBK//IGNORE", $v_value['purchase_order_num']);
                        $v_value_tmp['create_name']        = iconv("UTF-8", "GBK//IGNORE", $v_value['create_name']);
                        $v_value_tmp['create_time']        = iconv("UTF-8", "GBK//IGNORE", $v_value['create_time']);
                        $v_value_tmp['update_time']        = iconv("UTF-8", "GBK//IGNORE", $v_value['update_time']);
                        $v_value_tmp['push_status']        = iconv("UTF-8", "GBK//IGNORE", getParcelUrgentState($v_value['push_status']));//推送状态
                        $v_value_tmp['push_res']           = iconv("UTF-8", "GBK//IGNORE", $v_value['push_res']);//推送结果

                        fputcsv($fp, $v_value_tmp);

                    }
                }
                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$file_name;
        $this->success_json($down_file_url);
    }

}
