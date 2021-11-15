<?php
/**
 * Created by PhpStorm.
 * 二次包装控制器
 * User: Jaden
 * Date: 2019/01/16 
 */

class Repackaging extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('product_repackage_model','repackage');
        $this->load->model('supplier_model','supplier_model',false,'supplier');
        $this->load->model('product_model','product_model',false,'product');
        $this->load->helper('status_product');
    }

    /**
     * 二次包装列表
     * /purchase/repackaging/two_packaging_list
     * @author Jaden 2019-1-17
    */
    public function two_packaging_list(){
        $params = [
            'sku' => $this->input->get_post('sku'), // SKU
            'audit_status' => $this->input->get_post('audit_status'), // 审核状态(0.默认未审核,1审核通过,2审核不通过)
        ];
        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;
        $field ='id,sku,audit_status,supplier_name,product_name,create_user_name,create_time,audit_time,audit_user_name,create_time';
        $orders_info = $this->repackage->get_product_repackage_list($params, $offset, $limit,$field);
        $orders_info['key'] = array('sku','审核状态','供应商名称','产品名称','创建人','创建时间','操作');
        $drop_down_box['is_abnormal_list'] = getproductRepackageStatus();//审核下拉
        $data_list = $orders_info['value'];
        $orders_info['drop_down_box'] = $drop_down_box;
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        if(!empty($data_list)){
            foreach ($data_list as $key => $value) {
                $orders_info['value'][$key]['audit_status'] = getproductRepackageStatus($value['audit_status']);
            }
            $this->success_json($orders_info);
        }else{
            $this->success_json($orders_info,null,'暂无相关数据');
        }
        
    }

    /**
     * 二次包装列表导入``
     * /purchase/repackaging/import_packaging
     * @author Jaden 2019-1-17
    */
    public function import_packaging(){

        $import_json = $this->input->get_post('import_arr');
        $result_list = json_decode($import_json,true);
        $errorMsg = '';
        $n = count($result_list);
        $nn = 0;
        $j = 0;
        $i = 0;
        $repackage_arr = array();
        foreach ($result_list as $key => $value) {
            if($key==0){
                continue;
            }
            $sku =$value[0];//SKU
            $product_name = $value[1];//产品名称
            $supplier_name = $value[2];//供应商名称

            if(empty($sku) || empty($product_name) || empty($supplier_name)){
                $j++;
                continue;
            }
            //检测数据表是否存在该SKU
            $getonedata = $this->repackage->get_one($sku);
            if(!empty($getonedata)){
                $nn++;
                continue;
            }

            $i++;
            //根据供应商名称查找supplier_code
            $supplier_info = $this->supplier_model->get_supplier_by_name($supplier_name,false);

            $add_arr['sku'] = $sku;
            $add_arr['product_name'] = $product_name;
            $add_arr['supplier_name'] = $supplier_name;
            $add_arr['supplier_code'] = !empty($supplier_info)?$supplier_info['supplier_code']:'';
            $add_arr['create_user_name'] = getActiveUserName();
            $add_arr['create_time'] = date('Y-m-d H:i:s');
            array_push($repackage_arr,$add_arr);
        }
        $result = $this->repackage->insert_batch_all($repackage_arr);
        if($result){
            $not_num = $j+$nn;
            $msg = '共'.($n-1).'条记录，导入成功'.$i.'条数据,导入失败'.$not_num.'条数据！';
            $this->success_json($msg);
        }else{
            $this->error_data_json('导入失败,查看SKU是否已经存在！');
        }
        

    }

    /**
     * 删除数据
     * /purchase/repackaging/delete_pack
     * @author Jaden 2019-1-17
    */
    public function delete_pack(){
        $ids = $this->input->get_post('ids');
        if(empty($ids)){
            $this->error_data_json('请传参数');
        }
        $ids_arr = explode(',', $ids);
        if(!is_array($ids_arr)){
            $this->error_data_json('参数有误');
        }
        $this->db->where_in('id', $ids_arr);
        $update_status = $this->db->update($this->repackage->tableName(), array('status'=>2));
        if($update_status){
            $this->success_json('删除成功');
        }else{
            $this->error_data_json('删除失败，请稍后再试');
        }
    }

    /**
     * 审核
     * /purchase/repackaging/examine
     * @author Jaden 2019-1-17
    */
    public function examine(){
        $ids = $this->input->get_post('ids');
        //$ids = '12,13,22,38,46';
        $audit_status = $this->input->get_post('audit_status');
        //$audit_status = 2;
        $remarks = $this->input->get_post('remarks');
        if(empty($ids)){
            $this->error_data_json('请传参数');
        }
        if(empty($audit_status) || !in_array($audit_status, array(PRODUCT_REPACKAGE_STATUS_AUDIT_PASS,PRODUCT_REPACKAGE_STATUS_AUDIT_NO_PASS))){
            $this->error_data_json('请选择审核状态');
        }

        if(3==$audit_status){//审核不通过
            if(empty($remarks)){
                $this->error_data_json('请填写原因');
            }
        }

        $ids_arr = explode(',', $ids);
        if(!is_array($ids_arr)){
            $this->error_data_json('参数有误');
        }
        if(2==$audit_status){//审核通过，推送数据到数据中心
            //读取配置文件参数，获取推送地址
            $this->load->config('api_config', FALSE, TRUE);
            if (!empty($this->config->item('service_data'))) {
                $warehouse_data_info = $this->config->item('service_data');
                $_url_ip = isset($warehouse_data_info['ip'])?$warehouse_data_info['ip']:'';
                $_url_push_void = isset($warehouse_data_info['push_product_repackage'])?$warehouse_data_info['push_product_repackage']:'';
                if(empty($_url_ip) or empty($_url_push_void)){
                    exit('推送地址缺失');
                }
                $url = $_url_push_void;
            }
            if(empty($url)){
                $this->error_json('推送地址缺失');
            }
            $field ='id,sku';
            $params['ids'] = $ids;
            $orders_info = $this->repackage->get_product_repackage_list($params, '', '',$field,true);
            $data_list = $orders_info['value'];
            foreach ($data_list as $key => $value) {
                //$product_info = $this->product_model->get_product_info($value['sku']);
                $data_list[$key]['sku'] = $value['sku'];
                $data_list[$key]['is_repackage'] = 1;
                $data_list[$key]['is_purchase_new'] = 1;//标记是否是新采购系统数据
                /*
                $data_list[$key]['is_new'] = isset($product_info['is_new'])?isset($product_info['is_new']):0;
                $data_list[$key]['is_boutique'] = isset($product_info['is_boutique'])?isset($product_info['is_boutique']):0;
                $data_list[$key]['is_weightdot'] = isset($product_info['is_weightdot'])?isset($product_info['is_weightdot']):0;
                */
            }
            $pust_data['purchase_sku'] = json_encode($data_list);      
            $pust_data['token'] = json_encode(stockAuth());
            $response = getCurlData($url,$pust_data);
            $_result = json_decode($response,true);
            if (isset($_result['success_list']) && !empty($_result['success_list'])) {
                $sku_arr = $_result['success_list'];
                $this->db->where_in('sku', $sku_arr)->update('product', array('is_repackage'=>1));
            }else{
               $this->error_data_json('数据推送到数据中心失败'); 
            }
        }
        $examine_data['audit_status'] = $audit_status;
        $examine_data['remarks'] = $remarks;
        $examine_data['audit_user_name'] = getActiveUserName();
        $examine_data['audit_time'] = date('Y-m-d H:i:s');
        $update_examin = $this->db->where_in('id', $ids_arr)->update($this->repackage->tableName(), $examine_data);
        if($update_examin){
            $this->success_json('操作成功');
        }else{
            $this->error_data_json('操作失败，请稍后再试');
        }
    }

    
    /**
     * 获取审核状态
     * /purchase/repackaging/get_product_repackage_status
     * @author jaxton 2019-1-17
    */
    public function get_product_repackage_status(){
        $list=$this->repackage->get_product_repackage_status();
        $this->success_json($list);
    }

    //测试用
    public function test(){
        echo '<form action="/index.php/purchase/repackaging/import_packaging" method="post" enctype="multipart/form-data">
             <input type="file" class="packag_file" name="packag_file" />
             <button type="submit" class="but1">上传</button>
        </form>';
    }

}