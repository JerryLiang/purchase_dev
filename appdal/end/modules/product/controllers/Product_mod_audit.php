<?php
/**
 * 产品修改审核控制器
 * User: Jaxton
 * Date: 2019/01/24 15:00
 */

class Product_mod_audit extends MY_Controller{

	public function __construct(){
        parent::__construct();
        $this->load->model('Product_mod_audit_model','product_mod');
        $this->load->helper('status_product');
        $this->load->model('user/User_group_model');
    }

    /**
    * 获取列表
    * /product/product_mod_audit/get_product_list
    * @author Jaxton 2019/01/21
    */
    public function get_product_list(){
    	$params=[
    		'sku' => $this->input->get_post('sku'),
	    	'create_user_id' => $this->input->get_post('create_user_id'),//申请人
	    	'is_sample' => $this->input->get_post('is_sample'),//是否拿样
	    	'sample_check_result' => $this->input->get_post('sample_check_result'),//样品检验结果
	    	'audit_status' => $this->input->get_post('audit_status'),//审核状态
            'audit_time_start' => $this->input->get_post('examine_start_time'), // 审核开始时间
            'audit_time_end'   => $this->input->get_post('examin_end_time'), // 审核结束时间
            'product_change'   => $this->input->get_post('product_change'), // 商品价格变化
            'supplier'         => $this->input->get_post('supplier'), // 供应商编码
            'create_user_name' => $this->input->get_post('create_user_name'), // 申请人姓名
            'is_purchaseing'   => $this->input->get_post('is_purchaseing'), // 是否需要待采购
            'is_type'          => $this->input->get_post('is_type'), // 类型,
            'reason'           => $this->input->get_post('reason_list'),
            'product_line_id'  =>$this->input->get_post('product_line_id'), // 产品线
            'group_ids'        => $this->input->get_post('group_ids'), // 组别ID
            'is_audit_type'          => $this->input->get_post('is_audit_type'),
            'start_ratio'      => $this->input->get_post('start_ratio'),
            'end_ratio'        => $this->input->get_post('end_ratio'),
            'create_time_start'        => $this->input->get_post('create_time_start'),
            'create_time_end'  => $this->input->get_post('create_time_end'),
            'scree_time_start' => $this->input->get_post('scree_time_start'),
            'scree_time_end'   => $this->input->get_post('scree_time_end')
    	];
    	

    	$page_data=$this->format_page_data();
        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        if(!empty($params)){

            foreach($params as $params_key=>$params_value){

                if($params_value == NULL || empty($params_value) || $params_value == " "){

                    $params[$params_key] = trim($params_value);
                }
            }
        }
        $field = "b.supply_status,a.new_supply_status,a.old_supply_status,a.create_user_id,a.*,a.maintain_ticketed_point,concat(a.old_ali_ratio_own,':',a.old_ali_ratio_out) as old_ali_ratio_qty,concat(a.new_ali_ratio_own,':',a.new_ali_ratio_out) as new_ali_ratio_qty,b.product_img_url,b.product_thumb_url,b.create_user_name as develop_user_name,b.create_time as develop_time,b.product_status";
        $result = $this->product_mod->get_product_list($params,$page_data['limit'],$page_data['offset'],$field,$page_data['page']);

        $formart_data= $this->product_mod->formart_product_list($result['data_list']);
    
        $role_name=get_user_role();//当前登录角色
        $data_role= getRolexiao();
        $results = ShieldingData($formart_data,['new_supplier_name','old_supplier_name','new_supplier_code','old_supplier_code'],$role_name,$data_role);
        $data_list=[
        	'value'=>$results,
        	//'key' => ['ID','审核状态','申请人/申请时间','产品图片','sku','产品名称','产品信息','单价','税点','系统与1688单位关系','供应商名称','箱内数','外箱尺寸','链接','是否拿样','是否代采','样品检验结果','备注','修改原因','是否包邮'],
        	'drop_down_box' => $this->product_mod->get_down_list()
        ];

        $headerlog = $this->product_mod->headerlog();
        $this->load->model('product_model');

        $uid = $this->input->get_post('uid');
        $flag = ($params['is_audit_type'] == 1)?'product_list_2':'product_list_1';
        $searchData = $this->product_model->get_user_header($uid,$flag,$headerlog);
        $data_list['key'] = $searchData;
        $this->success_json($data_list,$result['paging_data']);

    }

    /**
     * 选择下拉框
     */
    function get_drop_box(){
        $data['drop_down_box'] = $this->product_mod->get_down_list();
        $this->success_json($data);
    }

    /**
    * 审核
    * /product/product_mod_audit/product_audit
    * @author Jaxton 2019/01/21
    */
    public function product_audit(){
    	$id = $this->input->get_post('id');
    	if(empty($id)) $this->error_json('缺少参数ID或者类型错误');
    	$audit_result = $this->input->get_post('audit_result');  //审核结果，1通过，2驳回
    	if(empty($audit_result) || !in_array($audit_result,[1,2])) $this->error_json('审核结果错误，请检查');
    	$remark = $this->input->get_post('remark');
    	if($audit_result == 2 && empty($remark)) {
            $this->error_json('驳回请填写原因');
        }

    	$result = $this->product_mod->product_audit($id,$audit_result,$remark);
    	
    	if($result['success']){
    	    $this->success_json([],null,'操作成功');
    	}else{
    	    $this->error_json($result['error_msg']);
    	}
    }

    
    /**
    * 品控审核(产品系统)
    * /product/product_mod_audit/product_control_audit
    * @author Jaxton 2019/01/28
    */
    public function product_control_audit(){
        $id = $this->input->get_post('id');//产品修改信息ID
        if(empty($id)) $this->error_json('缺少参数ID');
        $audit_result = $this->input->get_post('audit_result');  //审核结果，1通过，2驳回
        if(empty($audit_result) || !in_array($audit_result,[1,2])) $this->error_json('审核结果错误，请检查');
        $result=$this->product_mod->product_control_audit($id,$audit_result);
        if($result['success']){
            $this->success_json([],null,'操作成功');
        }else{
            $this->error_json($result['error_msg']);
        }
    }


    /**
    * 产品审核列表导出
    * /product/product_mod_audit/product_audit_export
    * @author Jaxton 2019/01/28
    */
    public function product_audit_export(){
        ini_set('memory_limit','-1');
        set_time_limit(0);
        $this->load->helper('export_csv');
        $ids = $this->input->get_post('ids');

        $export_mode = $this->input->get_post('export_mode');//导出格式 1[csv] 2[excle]
        if(empty($export_mode)){
            $export_mode = 1;
        }else{
            $export_mode = $export_mode;
        }
        if(!empty($ids)){
            $ids_arr = explode(',', $ids);
            $params['id']   = $ids_arr;
        }else {
            $params = [
                'sku' => $this->input->get_post('sku'),
                'create_user_id' => $this->input->get_post('create_user_id'),//申请人
                'is_sample' => $this->input->get_post('is_sample'),//是否拿样
                'sample_check_result' => $this->input->get_post('sample_check_result'),//样品检验结果
                'audit_status' => $this->input->get_post('audit_status'),  //审核状态
                'create_user_name' => $this->input->get_post('create_user_name'),//申请人
                'audit_time_start' => $this->input->get_post('examine_start_time'), // 审核开始时间
                'audit_time_end' => $this->input->get_post('examin_end_time'), // 审核结束时间
                'is_purchaseing' => $this->input->get_post('is_purchaseing'), // 是否需要待采购
                'product_change' => $this->input->get_post('product_change'),
                'reason' => $this->input->get_post('reason_list'),
                'is_type' => $this->input->get_post('is_type'),
                'supplier'         => $this->input->get_post('supplier'), // 供应商编码
                 'product_line_id' =>$this->input->get_post('product_line_id'), // 产品线
                'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
                'is_audit_type' =>$this->input->get_post('is_audit_type'),
                'create_time_start'        => $this->input->get_post('create_time_start'),
                'create_time_end'  => $this->input->get_post('create_time_end'),
                'scree_time_start' => $this->input->get_post('scree_time_start'),
                'scree_time_end' =>$this->input->get_post('scree_time_end')
            ];
        }
        $field='a.*,b.supply_status,b.product_img_url,b.product_thumb_url,b.create_user_name as develop_user_name,b.create_time as develop_time,b.product_status';
        $this->load->model('system/Data_control_config_model');
        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }
        $total =  $this->product_mod->get_product_list($params,1,'',$field,'',true);
        //export_excel
        $total = $total['paging_data']['total'];
        $limit = 1000;
        $page = ceil($total / $limit);
        if( $export_mode == 1) {
            //转入下载中心--start
            try {
                $ext = 'csv';
                $params['role_name'] = get_user_role();
                $result = $this->Data_control_config_model->insertDownData($params, 'PRODUCTAUDITDATA', '产品管理审核', getActiveUserName(), $ext, $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }

            die();
            //转入下载中心--end

            $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
            // 表示CSV格式

            $file_name = 'product_uplodate_log' . time() . '.csv';

            $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file, 'w');
            $fp = fopen($product_file, "a");
            $heads = array('审核状态', '审核人', '审核时间', '审核备注',
                '申请人', '申请时间', '产品图片', 'sku',
                '产品名称', '产品状态', '产品线', '开发员',
                '创建时间', '修改前单价', '修改后单价', '修改前税点',
                '修改后税点', '修改前供应商', '修改后供应商','修改前箱内数',
                '修改后箱内数','修改前外箱尺寸','修改后外箱尺寸', '修改前链接',
                '修改后链接', '是否拿样', '修改前代采', '修改后代采', '样品检验结果', '确认人', '确认时间', '确认备注', '申请备注',
                '申请类型','修改原因','所属小组','结算方式(修改前)','结算方式(修改后),是否包邮（修改前）','是否包邮(修改后)','价格变化比例');
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            for ($i = 1; $i <= $page; ++$i) {
                $offset = ($i - 1) * $limit;
                $result = $this->product_mod->get_product_list($params, $limit, $offset, $field, '', false);
                $formart_data = $this->product_mod->formart_product_list($result['data_list']);

                if (!empty($formart_data)) {

                    $role_name=get_user_role();//当前登录角色
                    $data_role= getRolexiao();
                    $formart_data = ShieldingData($formart_data,['new_supplier_name','old_supplier_name','new_supplier_code','old_supplier_code'],$role_name,NULL);
                    foreach ($formart_data as $key => $value) {
                        $v_value_tmp = [];
                        $v_value_tmp['audit_status'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_status']);
                        $v_value_tmp['audit_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_user_name']);
                        $v_value_tmp['audit_time'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_time']);
                        $v_value_tmp['audit_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_remark']);
                        $v_value_tmp['create_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);
                        $v_value_tmp['create_time'] = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);
                        $v_value_tmp['product_img_url'] = iconv("UTF-8", "GBK//IGNORE", $value['product_img_url']);
                        $v_value_tmp['sku'] = iconv("UTF-8", "GBK//IGNORE", $value['sku']);
                        $v_value_tmp['product_name'] = iconv("UTF-8", "GBK//IGNORE", $value['product_name']);
                        $v_value_tmp['product_status'] = iconv("UTF-8", "GBK//IGNORE", $value['product_status']);
                        $v_value_tmp['product_line_name'] = iconv("UTF-8", "GBK//IGNORE", ($value['product_line_name']));
                        $v_value_tmp['develop_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['develop_user_name']);
                        $v_value_tmp['develop_time'] = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);
                        $v_value_tmp['old_supplier_price'] = iconv("UTF-8", "GBK//IGNORE", $value['old_supplier_price']);
                        $v_value_tmp['new_supplier_price'] = iconv("UTF-8", "GBK//IGNORE", $value['new_supplier_price']);
                        $v_value_tmp['old_ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $value['old_ticketed_point']);
                        $v_value_tmp['new_ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $value['new_ticketed_point']);
                        $v_value_tmp['old_supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $value['old_supplier_name']);
                        $v_value_tmp['new_supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $value['new_supplier_name']);

                        $v_value_tmp['new_inside_number'] = iconv("UTF-8", "GBK//IGNORE", $value['new_inside_number']);
                        $v_value_tmp['old_inside_number'] = iconv("UTF-8", "GBK//IGNORE", $value['old_inside_number']);

                        $v_value_tmp['new_box_size'] = iconv("UTF-8", "GBK//IGNORE", $value['new_box_size']);
                        $v_value_tmp['old_box_size'] = iconv("UTF-8", "GBK//IGNORE", $value['old_box_size']);


                        $v_value_tmp['old_product_link'] = iconv("UTF-8", "GBK//IGNORE", $value['old_product_link']);
                        $v_value_tmp['new_product_link'] = iconv("UTF-8", "GBK//IGNORE", $value['new_product_link']);
                        $v_value_tmp['is_sample'] = iconv("UTF-8", "GBK//IGNORE", $value['is_sample']);
                        if ($value['old_is_purchasing'] == 1) {
                            $v_value_tmp['old_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "否");
                        } else {
                            $v_value_tmp['old_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "是");
                        }

                        if ($value['new_is_purchasing'] == 1) {
                            $v_value_tmp['new_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "否");
                        } else {
                            $v_value_tmp['new_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "是");
                        }

                        $v_value_tmp['sample_check_result'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_check_result']);
                        $v_value_tmp['sample_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_user_name']);
                        $v_value_tmp['sample_time'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_time']);
                        $v_value_tmp['sample_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_remark']);
                        $v_value_tmp['sample_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_remark']);
                        $v_value_tmp['create_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['create_remark']);
                        if (!empty($value['update_content'])) {
                            $content_string = '';
                            foreach ($value['update_content'] as $content_key => $content_value) {
                                $content_string .= $content_value . ",";
                                $v_value_tmp['update_content'] = iconv("UTF-8", "GBK//IGNORE", $content_string);
                            }

                        } else {
                            $v_value_tmp['update_content'] = '';
                        }

                        $is_new_shipping = $is_old_shipping = '';

                        if($value['is_new_shipping'] == 1){

                            $is_new_shipping = "包邮";
                        }else{
                            $is_new_shipping = "不包邮";
                        }

                        if($value['is_old_shipping'] == 1){

                            $is_old_shipping = "包邮";
                        }else{
                            $is_old_shipping = "不包邮";
                        }

                        $v_value_tmp['reason'] = iconv("UTF-8", "GBK//IGNORE", $value['reason']);

                        $v_value_tmp['old_shipping'] = iconv("UTF-8", "GBK//IGNORE", $is_old_shipping);
                        $v_value_tmp['new_shipping'] = iconv("UTF-8", "GBK//IGNORE", $is_new_shipping);
                        $v_value_tmp['cuttheprice_ch'] = $value['cuttheprice_ch'];
                        fputcsv($fp, $v_value_tmp);

                    }
                }
                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            //$down_host = "local.webfront.com/";
            $down_file_url = $down_host . 'download_csv/' . $file_name;
            $this->success_json($down_file_url);
        }else{
            $tax_list_tmp = [];
            $role_name=get_user_role();//当前登录角色
            $data_role= getRolexiao();

            for ($i = 1; $i <= $page; ++$i) {

                $offset = ($i - 1) * $limit;
                $result = $this->product_mod->get_product_list($params, $limit, $offset, $field, '', false);
                $formart_data = $this->product_mod->formart_product_list($result['data_list']);

                $formart_data = ShieldingData($formart_data,['new_supplier_name','old_supplier_name','new_supplier_code','old_supplier_code'],$role_name,$data_role);
                if (!empty($formart_data)) {

                    foreach ($formart_data as $key => $value) {

                        $v_value_tmp                       = [];
                        $v_value_tmp['audit_status']                = $value['audit_status'];
                        $v_value_tmp['audit_user_name']                = $value['audit_user_name'];
                        $v_value_tmp['audit_time']                = $value['audit_time'];
                        $v_value_tmp['audit_remark']                = $value['audit_remark'];
                        $v_value_tmp['create_user_name']                = $value['create_user_name'];
                        $v_value_tmp['create_time']                = $value['create_time'];
                        $v_value_tmp['product_img_url']                = $value['product_img_url'];
                        $v_value_tmp['sku']                = $value['sku'];
                        $v_value_tmp['product_name']                = $value['product_name'];
                        $v_value_tmp['product_status']                = $value['product_status'];
                        $v_value_tmp['product_line_name']                = $value['product_line_name'];
                        $v_value_tmp['develop_user_name']                = $value['develop_user_name'];
                        $v_value_tmp['develop_time']                = $value['develop_time'];
                        $v_value_tmp['old_supplier_price']                = $value['old_supplier_price'];
                        $v_value_tmp['new_supplier_price']                = $value['new_supplier_price'];
                        $v_value_tmp['old_ticketed_point']                = $value['old_ticketed_point'];
                        $v_value_tmp['new_ticketed_point']                = $value['new_ticketed_point'];
                        $v_value_tmp['old_supplier_name']                = $value['old_supplier_name'];
                        $v_value_tmp['new_supplier_name']                = $value['new_supplier_name'];

                        $v_value_tmp['new_inside_number'] =  $value['new_inside_number'];
                        $v_value_tmp['old_inside_number'] = $value['old_inside_number'];

                        $v_value_tmp['new_box_size'] =  $value['new_box_size'];
                        $v_value_tmp['old_box_size'] = $value['old_box_size'];



                        $v_value_tmp['old_product_link']                = $value['old_product_link'];
                        $v_value_tmp['new_product_link']                = $value['new_product_link'];
                        $v_value_tmp['is_sample']                = $value['is_sample'];
                        if( $value['old_is_purchasing'] == 1)
                        {
                            $v_value_tmp['old_is_purchasing'] = "否";
                        }else{
                            $v_value_tmp['old_is_purchasing'] = "是";
                        }

                        if( $value['new_is_purchasing'] == 1)
                        {
                            $v_value_tmp['new_is_purchasing'] = "否";
                        }else{
                            $v_value_tmp['new_is_purchasing'] = "是";
                        }
                        $v_value_tmp['sample_check_result']                = $value['sample_check_result'];
                        $v_value_tmp['sample_user_name']                = $value['sample_user_name'];
                        $v_value_tmp['sample_time']                = $value['sample_time'];
                        $v_value_tmp['sample_remark']                = $value['sample_remark'];
                        $v_value_tmp['sample_remark']                = $value['sample_remark'];
                        $v_value_tmp['create_remark']                = $value['create_remark'];
                        if( !empty($value['update_content']))
                        {
                            $content_string = '';
                            foreach( $value['update_content'] as $content_key=>$content_value)
                            {
                                $content_string .= $content_value.",";
                                $v_value_tmp['update_content'] = $content_string;
                            }

                        }else{
                            $v_value_tmp['update_content'] = '';
                        }
                        $v_value_tmp['reason'] =  $value['reason'];
                        $v_value_tmp['groupName'] = $value['groupName'];
                        $oldpaymentData = $this->Supplier_model->get_supplier_payment($value['old_supplier_code']);
                        $v_value_tmp['old_settlement_method'] = $oldpaymentData;

                        $newpaymentData = $this->Supplier_model->get_supplier_payment($value['new_supplier_code']);
                        $v_value_tmp['new_settlement_method'] =  $newpaymentData;

                        $is_new_shipping = $is_old_shipping = '';

                        if($value['is_new_shipping'] == 1){

                            $is_new_shipping = "包邮";
                        }else{
                            $is_new_shipping = "不包邮";
                        }

                        if($value['is_old_shipping'] == 1){

                            $is_old_shipping = "包邮";
                        }else{
                            $is_old_shipping = "不包邮";
                        }
                        $v_value_tmp['old_shipping'] = $is_old_shipping;
                        $v_value_tmp['new_shipping'] = $is_new_shipping;
                        $v_value_tmp['cuttheprice'] = $value['cuttheprice'];

                        $tax_list_tmp[] = $v_value_tmp;
                    }


                }

            }
            $result = array(
                'tax_list_tmp' => $tax_list_tmp,
                'export_mode'=> $export_mode,
                'field_img_name' => array('产品图片'),
                'field_img_key' => array('product_img_url'),
            );

            $this->success_json($result);
        }
    }

    public function get_product_log(){

        $skus =  $this->input->get_post('sku');
        if( empty($skus) ) {

            $this->error_json('缺少SKU参数');
        }

        $sku_logs = $this->product_mod->get_product_log($skus);
        if( !empty($sku_logs) ) {

            foreach($sku_logs as $key=>&$value ) {

                if( $value['create_user_name'] == $value['audit_user_name'] && $value['create_time'] == $value['audit_time']) {

                    $value['create_remark'] = "系统自动为---信息修改申请变更";
                }
            }
        }

        $this->success_json($sku_logs);
    }

    /**
     * 获取审核日志信息
     * @author :luxu
     * @time:2020/3/10
     **/
    public function getProductAuditLog()
    {
        try{

            $skus =  $this->input->get_post('sku');
            if( empty($skus) ) {

                $this->error_json('缺少SKU参数');
            }
            $id =  $this->input->get_post('id');

            $audit_logs = $this->product_mod->getProductAuditLog($skus,$id);
            if( !empty($audit_logs) ){
                foreach($audit_logs as $key=>$value){
                    if( $value['status'] == 1){
                        $audit_logs[$key]['status_ch'] = "审核通过";
                    }else{
                        $audit_logs[$key]['status_ch'] = "审核驳回";
                    }
                }
            }
            $this->success_json($audit_logs);
        }catch (Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    public function get_supplier_avg(){

        $supplier_code = $this->input->get_post("supplier_code");
        $result = $this->product_mod->get_supplier_avg($supplier_code);
        $this->success_json($result);

    }



}