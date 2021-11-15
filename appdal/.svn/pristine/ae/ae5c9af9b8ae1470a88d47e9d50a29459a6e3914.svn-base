<?php
/**
 * 异常列表控制器
 * User: Jaxton
 * Date: 2019/01/16 10:00
 */

class Abnormal_list extends MY_Controller{
    private $analysis_url = '/order/addressExtraction/detailAddress';

    public function __construct(){
        parent::__construct();
        $this->load->model('Abnormal_list_model','abnormal_model');
        $this->load->helper('abnormal');
    }

    /**
    * 获取异常数据列表
    * /abnormal/abnormal_list/get_abnormal_list
    * @author Jaxton 2019/01/16
    */
    public function get_abnormal_list(){
    	$params=[
    		'sku'=>$this->input->get_post('sku'),
	    	'buyer'=>$this->input->get_post('buyer'),//采购员
	    	'defective_id'=>$this->input->get_post('defective_id'),//异常单号
	    	'pur_number'=>$this->input->get_post('pur_number'),//采购单号
	    	'express_code'=>$this->input->get_post('express_code'),//快递单号
	    	'is_handler'=>$this->input->get_post('is_handler'),//是否处理
	    	'handler_type'=>$this->input->get_post('handler_type'),//处理类型
	    	'abnormal_type'=>$this->input->get_post('abnormal_type'),//异常类型
            'defective_type'=>$this->input->get_post('defective_type'),//次品类型
            'is_purchasing'=>$this->input->get_post('is_purchasing'),//是否代采
            'pull_time_start'=>$this->input->get_post('pull_time_start'),//拉取时间开始
	    	'pull_time_end'=>$this->input->get_post('pull_time_end'),//拉取时间截止
	    	'waiting_process'=>$this->input->get_post('waiting_process'),//等待处理中
	    	'is_left_stock'=>$this->input->get_post('is_left_stock'),//是否缺货
	    	'duty_group'=>$this->input->get_post('duty_group'),//责任小组
            'deal_used_start'=>$this->input->get_post('deal_used_start'),//处理时效开始
            'deal_used_end'=>$this->input->get_post('deal_used_end'),//处理时效截止
            'documentary_id'=>$this->input->get_post('documentary_id'),//跟单员id
            'express_no'=>$this->input->get_post('express_no'),//退货快递单号
            'product_line_id'=>$this->input->get_post('product_line_id'),//产品线id
            'warehouse_code'=>$this->input->get_post('warehouse_code'),//仓库编码（多个仓库编码用逗号分隔）
            'handle_warehouse'=>$this->input->get_post('handle_warehouse'),//处理仓库（多个仓库编码用逗号分隔）
            'supplier_code'=>$this->input->get_post('supplier_code'),//供应商编码
            'sort_by'=>$this->input->get_post('sort_by'),//排序类型（asc-升序，desc-降序）
            'track_status'=>$this->input->get_post('track_status'),//轨迹状态（多个轨迹状态用逗号分隔）
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
            'handler_person'     => $this->input->get_post('handler_person'), // 处理人
            'handler_time_start'       => $this->input->get_post('handler_time_start'),//处理时间开始
            'handler_time_end'       => $this->input->get_post('handler_time_end'),//处理时间结束
            'product_name'        => $this->input->get_post('product_name'), // 产品名称
            'merchandiser_id'     => $this->input->get_post('merchandiser_id'),
            'department'          => $this->input->get_post('department'),
            'is_new' => $this->input->get_post('is_new')
        ];
        $page_data=$this->format_page_data();

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'label');
            }

            $params['groupdatas'] = $groupdatas;
        }

        if (isset($params['product_line_id'])&&!empty($params['product_line_id'])) {
            $children_id = $this->product_line_model->get_all_category($params['product_line_id']);
            $children_ids = explode(",", $children_id);
            $children_ids = array_filter($children_ids);
            $params['product_line_id'] = $children_ids;

        }

        if (!empty($params['merchandiser_id'])) {//获取跟单员
            $merchandiser_data = $this->Merchandiser_group_model->get_bind_info_by_user_id($params['merchandiser_id']);
            $params['merchandiser_data'] = $merchandiser_data;

        }





        $result=$this->abnormal_model->get_abnormal_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        if(isset($result["code"]) && $result["code"] == 0){
            $this->error_json($result["msg"]);
            return;
        }
        $result['data_list']['value']=$this->abnormal_model->formart_abnormal_list($result['data_list']['value']);

        $handlersDatas = getAbnormalHandleType();
//        if(empty($handlersDatas)){
//
//            $handlersDatas = '{"1":"退货","2":"次品退货","3":"次品转正","4":"正常入库","5":"不做处理","6":"挑好的入","7":"整批退货","8":"二次包装","10":"不做处理","15":"等待处理","16":"等待换图","17":"补发配件","18":"驳回-判断失误","19":"驳回-图片模糊","20":"驳回-数量有误","21":"驳回-描述不清","22":"继续补发","23":"退款","24":"报损","25":"转入库后退货"}';
//            $handlersDatas = json_decode($handlersDatas,true);
//        }
        if(!empty($result['data_list']['value'])){
            $result['data_list']['value'] = $this->abnormal_model->get_handler_type($result['data_list']['value'],$handlersDatas);
        }



        $this->success_json($result['data_list'],$result['paging_data']);
    }

    /**
     * 异常列表导出
     * @author jeff
    abnormal_list/abnormal_list/abnormal_export
     */
    public function abnormal_export(){
        set_time_limit(0);
        ini_set('memory_limit', '1000M');
        $this->load->helper('export_csv');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $ids = $this->input->get_post('id');

        if(!empty($ids)){
            $params['ids']   = $ids;
            $params['pull_time_start']=$this->input->get_post('pull_time_start');//拉取时间开始
            $params['pull_time_end']=$this->input->get_post('pull_time_end');//拉取时间截止
        }else{



            $params=[
                'sku'=>$this->input->get_post('sku'),
                'buyer'=>$this->input->get_post('buyer'),//采购员
                'defective_id'=>$this->input->get_post('defective_id'),//异常单号
                'pur_number'=>$this->input->get_post('pur_number'),//采购单号
                'express_code'=>$this->input->get_post('express_code'),//快递单号
                'is_handler'=>$this->input->get_post('is_handler'),//是否处理
                'handler_type'=>$this->input->get_post('handler_type'),//处理类型
                'abnormal_type'=>$this->input->get_post('abnormal_type'),//异常类型
                'defective_type'=>$this->input->get_post('defective_type'),//异常类型
                'is_purchasing'=>$this->input->get_post('is_purchasing'),//是否代采
                'pull_time_start'=>$this->input->get_post('pull_time_start'),//拉取时间开始
                'pull_time_end'=>$this->input->get_post('pull_time_end'),//拉取时间截止
                'waiting_process'=>$this->input->get_post('waiting_process'),//等待处理中
                'is_left_stock'=>$this->input->get_post('is_left_stock'),//是否缺货
                'duty_group'=>$this->input->get_post('duty_group'),//责任小组
                'deal_used_start'=>$this->input->get_post('deal_used_start'),//处理时效开始
                'deal_used_end'=>$this->input->get_post('deal_used_end'),//处理时效截止
                'documentary_id'=>$this->input->get_post('documentary_id'),//跟单员
                'express_no'=>$this->input->get_post('express_no'),//退货快递单号
                'product_line_id'=>$this->input->get_post('product_line_id'),//产品线id
                'warehouse_code'=>$this->input->get_post('warehouse_code'),//仓库编码（多个仓库编码用逗号分隔）
                'handle_warehouse'=>$this->input->get_post('handle_warehouse'),//处理仓库（多个仓库编码用逗号分隔）
                'supplier_code'=>$this->input->get_post('supplier_code'),//供应商编码
                'track_status'=>$this->input->get_post('track_status'),//轨迹状态（多个轨迹状态用逗号分隔）
                'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
                'handler_person'     => $this->input->get_post('handler_person'), // 处理人
                'handler_time_start'       => $this->input->get_post('handler_time_start'),//处理时间开始
                'handler_time_end'       => $this->input->get_post('handler_time_end'),//处理时间结束
                'product_name'        => $this->input->get_post('product_name'), // 产品名称
                'merchandiser_id'        => $this->input->get_post('merchandiser_id'), // 产品名称
                'is_new'        => $this->input->get_post('is_new'), // 产品名称


            ];
        }

        $params['is_type'] = $this->input->get_post('type');
        //接收参数
        $page_data=$this->format_page_data();
        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'label');
            }

            $params['groupdatas'] = $groupdatas;
        }


        if (isset($params['product_line_id'])&&!empty($params['product_line_id'])) {
            $children_id = $this->product_line_model->get_all_category($params['product_line_id']);
            $children_ids = explode(",", $children_id);
            $children_ids = array_filter($children_ids);
            $params['product_line_id'] = $children_ids;

        }


        $total= $this->abnormal_model->get_export_total($params,$page_data['offset'],$page_data['limit'],true);


        if( $params['is_type'] == 1) {
            $this->load->model('system/Data_control_config_model');

            try {
                $ext = 'csv';
                $result = $this->Data_control_config_model->insertDownData($params, 'ABNORMAL', '异常列表', getActiveUserName(), $ext, $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }
            die();
        }else{
            $heads = ['采购仓库','图片','登记仓库','异常单号','备货单号','快递单号','异常货位','采购单号','SKU','是否代采','SKU数量','一级产品线','供应商代码','供应商名称','异常类型','次品类型','处理类型','采购员','处理人','创建人','创建时间','异常描述','采购处理时间','采购处理描述',
                '退货快递单号','轨迹状态','采购员是否处理','采购员处理结果','仓库处理结果','处理时效(h)','备注','总重量(KG)','参考运费','所属组别',"处理时间","责任部门"];
            $data_values=[];
            $template_file = 'abnormal-'.date('YmdH_i_s').'.'.rand(1000, 9999).'.xlsx';
            if($total>=1) {
                $page_limit = 10000;
                for ($i = 1; $i <= ceil($total / $page_limit); $i++) {
                    $export_offset      = ($i - 1) * $page_limit;
                    $orders_export_info = $this->abnormal_model->get_abnormal_list_export($params, $export_offset, $page_limit);

                    $purchase_tax_list_export = $orders_export_info['data_list']['value'];

                    if ($purchase_tax_list_export) {

                        foreach ($purchase_tax_list_export as $value) {
                            $value_tmp['warehouse_name']     = $value['warehouse_name'];//采购仓库
                            $value_tmp['product_img_url'] = explode(",",$value['img_path_data'])[0];
                            $value_tmp['handle_warehouse']     =  $value['handle_warehouse_name'];//登记仓库
                            $value_tmp['defective_id']       = $value['defective_id'];//异常单号
                            $value_tmp['demand_number']      = $value['demand_number'];
                            $value_tmp['express_code']       = $value['express_code'];//快递单号
                            $value_tmp['exception_position'] = $value['exception_position'];//异常货位
                            $value_tmp['pur_number']         =  $value['pur_number'];//采购单号
                            $value_tmp['sku']                = $value['sku'];
                            $value_tmp['is_purchasing']                = $value['is_purchasing'];
                            $value_tmp['quantity']           =  $value['quantity'];//SKU数量
                            $value_tmp['product_line_name']  =  $value['product_line_name'];//一级产品线
                            $value_tmp['supplier_code']      = $value['supplier_code'];//供应商名称
                            $value_tmp['supplier_name']      = $value['supplier_name'];//供应商名称
                            $value_tmp['abnormal_type']  =$value['abnormal_type'];//异常类型
                            $value_tmp['defective_type']      = $value['defective_type'];//次品类型
                            $value_tmp['handler_type2']       =  $value['handler_type'];//处理类型
//                        $value_tmp['defective_type']     = iconv("UTF-8", "GBK//IGNORE", $value['defective_type']);//次品类型

                            $value_tmp['buyer']              = $value['buyer'];//采购员
                            $value_tmp['handler_person']     = isset($value['handler_person']) ? $value['handler_person']: "";//处理人
                            $value_tmp['create_user_name']   = $value['create_user_name'];//创建人
                            $value_tmp['create_time']        =  $value['create_time'];//创建时间
                            $value_tmp['abnormal_depict']    =  $value['abnormal_depict']; //异常描述
                            $value_tmp['handler_time']       =isset($value['handler_time'])? $value['handler_time'] : ""; //采购处理时间
                            $value_tmp['handler_describe']   = isset($value['handler_describe'])? $value['handler_describe'] : ""; //采购处理描述
                            $value_tmp['express_no']         =$value['return_express_no'];//退货快递单号
                            $value_tmp['track_status']     =  $value['track_status'];//轨迹状态
                            $value_tmp['is_handler']         = getAbnormalHandleResult($value['is_handler']);//是否处理
                            $value_tmp['handler_type']       = empty($value['handler_type'])?"未处理":getAbnormalHandleType($value['handler_type']);//采购员处理类型
                            $value_tmp['warehouse_handler_result'] = $value['warehouse_handler_result'];//仓库处理结果
                            $value_tmp['deal_used']          = $value['deal_used'];//处理时效
                            $value_tmp['note']               = $value['note'].' '.$value['add_note_person'].' '.$value['add_note_time'];//备注
                            $value_tmp['product_weight']                  = $value['product_weight'];
                            $value_tmp['reference_freight']                  = $value['reference_freight'];

                            $value_tmp['groupname']          = $value['groupName'];

                            $value_tmp['department_china_ch'] = $value['department_china_ch'];
                            $data_values[] = $value_tmp;

                        }
                    }
                }
            }


            $result = array(
                'heads' => $heads,
                'data_values' => $data_values,
                'file_name' => $template_file,
                'field_img_name' => array('图片'),
                'field_img_key' => array('product_img_url')
            );
            $this->success_json($result);
        }

        die();








        $template_file = 'abnormal-'.date('YmdH_i_s').'.'.rand(1000, 9999).'.csv';
        if($total>100000){//单次导出限制
            $template_file = 'product.xlsx';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=get_export_path_replace_host(get_export_path(),$down_host).$template_file;
            $this->success_json($down_file_url);
        }

        $product_file = get_export_path('abnormal').$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $fp = fopen($product_file, "a");
        $heads = ['采购仓库','登记仓库','异常单号','备货单号','快递单号','异常货位','采购单号','SKU','是否代采','SKU数量','一级产品线','供应商代码','供应商名称','异常类型','次品类型','处理类型','采购员','处理人','创建人','创建时间','异常描述','采购处理时间','采购处理描述',
            '退货快递单号','轨迹状态','采购员是否处理','采购员处理结果','仓库处理结果','处理时效(h)','备注','所属组别'];

        foreach($heads as $key => $item) {
            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);

        $x = 0;
        if($total>=1) {
            $page_limit = 10000;

            for ($i = 1; $i <= ceil($total / $page_limit); $i++) {
                $export_offset      = ($i - 1) * $page_limit;
                $orders_export_info = $this->abnormal_model->get_abnormal_list_export($params, $export_offset, $page_limit);

                $purchase_tax_list_export = $orders_export_info['data_list']['value'];

                if ($purchase_tax_list_export) {
                    foreach ($purchase_tax_list_export as $value) {
                        $value_tmp['warehouse_name']     = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_name']);//采购仓库
                        $value_tmp['handle_warehouse']     = iconv("UTF-8", "GBK//IGNORE", $value['handle_warehouse_name']);//登记仓库
                        $value_tmp['defective_id']       = iconv("UTF-8", "GBK//IGNORE", $value['defective_id']."\t");//异常单号
                        $value_tmp['demand_number']      = iconv("UTF-8", "GBK//IGNORE", $value['demand_number']."\t");
                        $value_tmp['express_code']       = iconv("UTF-8", "GBK//IGNORE", $value['express_code']."\t");//快递单号
                        $value_tmp['exception_position'] = iconv("UTF-8", "GBK//IGNORE", $value['exception_position']);//异常货位
                        $value_tmp['pur_number']         = iconv("UTF-8", "GBK//IGNORE", $value['pur_number']);//采购单号
                        $value_tmp['sku']                = iconv("UTF-8", "GBK//IGNORE", $value['sku']);
                        $value_tmp['is_purchasing']                = iconv("UTF-8", "GBK//IGNORE", $value['is_purchasing']);
                        $value_tmp['quantity']           = iconv("UTF-8", "GBK//IGNORE", $value['quantity']);//SKU数量
                        $value_tmp['product_line_name']  = iconv("UTF-8", "GBK//IGNORE", $value['product_line_name']);//一级产品线
                        $value_tmp['supplier_code']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']);//供应商名称
                        $value_tmp['supplier_name']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);//供应商名称
                        $value_tmp['abnormal_type']      = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_type']);//异常类型
                        $value_tmp['defective_type']      = iconv("UTF-8", "GBK//IGNORE", $value['defective_type']);//次品类型
                        $value_tmp['handler_type2']       = iconv("UTF-8", "GBK//IGNORE", $value['handler_type']);//处理类型
//                        $value_tmp['defective_type']     = iconv("UTF-8", "GBK//IGNORE", $value['defective_type']);//次品类型

                        $value_tmp['buyer']              = iconv("UTF-8", "GBK//IGNORE", $value['buyer']);//采购员
                        $value_tmp['handler_person']     = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_person']) ? $value['handler_person']: "");//处理人
                        $value_tmp['create_user_name']   = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);//创建人
                        $value_tmp['create_time']        = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);//创建时间
                        $value_tmp['abnormal_depict']    = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_depict']); //异常描述
                        $value_tmp['handler_time']       = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_time'])? $value['handler_time'] : ""); //采购处理时间
                        $value_tmp['handler_describe']   = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_describe'])? $value['handler_describe'] : ""); //采购处理描述
                        $value_tmp['express_no']         = iconv("UTF-8", "GBK//IGNORE", $value['express_no']."\t");//退货快递单号
                        $value_tmp['track_status']     = iconv("UTF-8", "GBK//IGNORE", $value['track_status']);//轨迹状态
                        $value_tmp['is_handler']         = iconv("UTF-8", "GBK//IGNORE", getAbnormalHandleResult($value['is_handler']));//是否处理
                        $value_tmp['handler_type']       = iconv("UTF-8", "GBK//IGNORE", empty($value['handler_type'])?"未处理":getAbnormalHandleType($value['handler_type']));//采购员处理类型
                        $value_tmp['warehouse_handler_result'] = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_handler_result']);//仓库处理结果
                        $value_tmp['deal_used']          = iconv("UTF-8", "GBK//IGNORE", $value['deal_used']);//处理时效
                        $value_tmp['note']               = iconv("UTF-8", "GBK//IGNORE", $value['note'].' '.$value['add_note_person'].' '.$value['add_note_time']);//备注
                        $value_tmp['groupname']          = iconv("UTF-8", "GBK//IGNORE", $value['groupName']);

                        $tax_list_tmp = $value_tmp;

                        $x ++;
//                        if($x > $total)break; // 大于总查询量时，强行阻断
                        fputcsv($fp, $tax_list_tmp);
                    }
                }
                //每1万条数据就刷新缓冲区
                if($i > 1){
                    ob_flush();
                    flush();
                }
            }
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=get_export_path_replace_host(get_export_path('abnormal'),$down_host).$template_file;
//        $down_file_url=APPPATH .'logs/'.$template_file;
        $this->success_json($down_file_url);
    }

    /**
    * 采购员处理
    * /abnormal/abnormal_list/buyer_handle
    * @author Jaxton 2019/01/16
    */
    public function buyer_handle(){
    	$params=[
    		'defective_id'=>$this->input->get_post('defective_id'),
    		'handler_type'=>$this->input->get_post('handler_type'),//处理类型
    		'pur_number'=>$this->input->get_post('pur_number'),//采购单号
    		'return_province'=>$this->input->get_post('return_province'),//退货省
    		'return_city'=>$this->input->get_post('return_city'),//退货市
    		'return_county'=>$this->input->get_post('return_county'),//退货县
    		'return_address'=>$this->input->get_post('return_address'),//退货详细地址
    		'return_linkman'=>$this->input->get_post('return_linkman'),//退货联系人
    		'return_phone'=>$this->input->get_post('return_phone'),//退货联系人电话
    		'handler_describe'=>$this->input->get_post('handler_describe'),//处理描述
            'type'=>$this->input->get_post('type'),//1已处理,2或者处理中
            'duty_group'=>$this->input->get_post('duty_group'),//责任小组
            'upload_image'=>$this->input->get_post('upload_image'),//上传图片
            'department'  => $this->input->get_post('department')
    	];
        if(empty($params['type'])) $params['type']=1;
    	if(empty($params['defective_id'])) $this->error_json('异常单号必须');
    	if(empty($params['handler_type'])) $this->error_json('处理类型必须选择');
    	if(empty($params['pur_number'])) $this->error_json('采购单号必须填写');
//    	if(empty($params['duty_group'])) $this->error_json('责任小组必须选择');
//    	if(empty($params['return_province']) || empty($params['return_city']) || empty($params['return_county']))
//    		$this->error_json('退货省、市、县(区)必须填');
//    	if(empty($params['return_address'])) $this->error_json('退货详细地址必填');
//    	if(empty($params['return_linkman']) || empty($params['return_phone'])) $this->error_json('退货联系人和联系电话必填');
    	
    	// $params=[
    	// 	'defective_id'=>'20180916094155210',
    	// 	'handler_type'=>1,//处理类型
    	// 	'pur_number'=>'PO353999',//采购单号
    	// 	'return_province'=>'广东',//退货省
    	// 	'return_city'=>'深圳',//退货市
    	// 	'return_county'=>'龙华',//退货县
    	// 	'return_address'=>'清湖科技园',//退货详细地址
    	// 	'return_linkman'=>'bos',//退货联系人
    	// 	'return_phone'=>'123456789',//退货联系人电话
    	// 	'handler_describe'=>'test'//处理描述
    	// ];
    	$result=$this->abnormal_model->buyer_handle_submit($params);

    	if($result['success']){
            $this->success_json([],null,'提交成功');
    	}else{
            $this->error_json($result['error_msg']);
    	}
    }

    /**
    * 驳回
    * /abnormal/abnormal_list/abnormal_reject
    * @author Jaxton 2019/01/16
    */
    public function abnormal_reject(){
    	$defective_id=$this->input->get_post('defective_id');
    	$reject_reason=$this->input->get_post('reject_reason');

    	if(empty($defective_id)) $this->error_json('异常单号必须');
    	if(empty($reject_reason)) $this->error_json('驳回原因必填');

    	$result=$this->abnormal_model->abnormal_reject($defective_id,$reject_reason);
    	if($result['success']){
            $this->success_json([],null,'操作成功');
    	}else{
            $this->error_json($result['error_msg']);
    	}
    }

    /**
    * 查看
    * /abnormal/abnormal_list/look_abnormal
    * @author Jaxton 2019/01/16
    */
    public function look_abnormal(){
    	$defective_id=$this->input->get_post('defective_id');

    	if(empty($defective_id)) $this->error_json('异常单号必须');

    	$result=$this->abnormal_model->get_look_abnormal($defective_id);
        $this->success_json($result);
    }

    /**
    * 获取是否处理
    * /abnormal/abnormal_list/get_is_handle
    * @author Jaxton 2019/01/21
    */
    public function get_is_handle(){
        $list=getAbnormalHandleResult();
        $this->success_json($list);
    }

    /**
    * 获取处理类型
    * /abnormal/abnormal_list/get_handle_type
    * @author Jaxton 2019/01/21
    */
    public function get_handle_type(){
        $list=getAbnormalHandleType();
        $this->success_json($list);
    }

    /**
    * 获取异常类型
    * /abnormal/abnormal_list/get_abnormal_type
    * @author Jaxton 2019/01/21
    */
    public function get_abnormal_type(){
        $list=getWarehouseAbnormalType();
        $this->success_json($list);
    }

    /**
    * 获取省
    * /abnormal/abnormal_list/get_province
    * @author Jaxton 2019/01/21
    */
    public function get_province(){
        $list=$this->abnormal_model->get_province();
        $this->success_json($list);
    }

    /**
    * 获取市
    * /abnormal/abnormal_list/get_city_county
    * @author Jaxton 2019/01/21
    */
    public function get_city_county(){
        $pid=$this->input->get_post('pid');
        if(empty($pid)){
            $this->error_json('请选择上一级行政区');
        }
        $list=$this->abnormal_model->get_city_county($pid);
        $this->success_json($list);
    }

    /**
    * 添加异常数据(仓库系统调取)
    * /abnormal/abnormal_list/add_abnormal_data
    * @author Jaxton 2019/01/28
    */
    public function add_abnormal_data(){
        $params=[
            'sku' => $this->input->get_post('sku'),
            'quantity' => $this->input->get_post('quantity'),//数量
            'exception_position' => $this->input->get_post('exception_position'),//异常货位
            'defective_type' => $this->input->get_post('defective_type'),//次品类型
            'pur_number' => $this->input->get_post('pur_number'),//采购单号
            'express_code' => $this->input->get_post('express_code'),//快递单号
            'abnormal_type' => $this->input->get_post('abnormal_type'),//异常类型
            'abnormal_depict' => $this->input->get_post('abnormal_depict'),//异常原因
            'img_path_data' => $this->input->get_post('img_path_data'),//图片地址
            'buyer' => $this->input->get_post('buyer'),//采购员名称
            'add_username' => $this->input->get_post('add_username'),//异常信息创建人
            'create_user_name' => $this->input->get_post('create_user_name'),//异常信息创建人
            'create_time' => $this->input->get_post('create_time'),//创建时间

        ];
        
        //验证字段是否
        $validate_result=$this->abnormal_model->validate_abnormal($params);
        if($validate_result['success']){
            $insert_result=$this->abnormal_model->add_abnormal_data($params);
            if($insert_result['success']){
                $this->success_json([],null,'操作成功');
            }else{
                $this->error_json($validate_result['error_msg']);
            }
        }else{
            $this->error_json($validate_result['error_msg']);
        }
    }


    /**
     * 获取异常单操作日志
     *  2019-02-01
     * @author jeff
     * /abnormal/abnormal_list/get_abnormal_operator_log
     */
    public function get_abnormal_operator_log(){
        $defective_id = $this->input->get_post('defective_id');
        if(empty($defective_id)){
            $this->error_json('参数【defective_id】缺失');
        }

        $page  = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if(empty($page) or $page < 0)
            $page = 1;
        $limit  = query_limit_range($limit);
        $offset = ($page - 1) * $limit;

        $result = $this->abnormal_model->get_abnormal_operator_log($defective_id, $limit, $offset);
        if($result){
            $this->success_json($result);
        }else{
            $this->error_json('没有相关数据');
        }
    }

    /**
     * @desc 获取统计的异常单数据
     * @author Jeff
     * @Date 2019/7/26 16:12
     * @return
     */
    public function get_sum_data()
    {
        $result = $this->abnormal_model->get_sum_data();
        if($result){
            $this->success_json($result);
        }else{
            $this->error_json('没有相关数据');
        }
    }

    /**
     * @desc 添加异常备注
     * @author Jeff
     * @Date 2019/9/18 13:43
     * @return
     */
    public function add_abnormal_note()
    {
        $defective_id = $this->input->get_post('defective_id');
        $abnormal_note=$this->input->get_post('abnormal_note');
        if(empty($defective_id)){
            $this->error_json('参数【defective_id】缺失');
        }

        if(empty($abnormal_note)) $this->error_json('备注必填');

        $result=$this->abnormal_model->add_abnormal_note($defective_id,$abnormal_note);
        if($result['success']){
            $this->success_json([],null,'操作成功');
        }else{
            $this->error_json($result['error_msg']);
        }
    }

    public function is_order_exist()
    {
        $purchase_number = $this->input->get_post('purchase_number');
        if (empty($purchase_number)) $this->error_json('请输入采购单号');
        $this->load->model('purchase/purchase_order_model');
        $order = $this->purchase_order_model->get_one($purchase_number,false);
        if (empty($order)){
            $this->error_json('不属于本系统po,请重新填写');
        }else{
            $this->success_json([],null,'po存在');
        }
    }

 /**
     * 采购员批量处理
     * /abnormal/abnormal_list/buyer_handle
     * @author Jaxton 2019/01/16
     */
    public function batch_buyer_handle(){
        $error_list = [];//提交失败的异常单号
        $params = $this->input->get_post('data');

        if (!is_json($params)) {
            $this->error_json('数据异常');

        }

        $data_list  = json_decode($params,true);

        if (empty($data_list)) {
            $this->error_json('空数据');

        }


        foreach ($data_list as $data_info) {

            if(empty($data_info['type'])) $data_info['type']=1;
            if(empty($data_info['defective_id'])) $this->error_json('异常单号必须');
            if(empty($data_info['handler_type'])) $this->error_json('处理类型必须选择');
            if(empty($data_info['pur_number'])) $this->error_json('采购单号必须填写');
            $result=$this->abnormal_model->buyer_handle_submit($data_info);
            if(!$result['success']){
                $error_list[] = '此异常单号'.$data_info['defective_id'].$result['error_msg'];
            }


        }
        if (!empty($error_list)) {
            $this->success_json($error_list,null,'提交完成');

        } else {
            $this->success_json([],null,'提交成功');


        }


    }





    //阿里接口智能分析
    public function analysis_return_address()
    {
        $data = $sku = $this->input->get_post('data');
        if (empty($data)) {
            $this->error_json('数据为空');
        }
        //$url = 'http://1z8580573g.51mypc.cn:33335/Api/Purchase/QualityAbnormal/getQualityAbnormalResult';
        $access_taken = getOASystemAccessToken();
        $post_url = GET_JAVA_DATA.$this->analysis_url."?access_token=".$access_taken;
        $post_data =json_encode(["address"=>$data],JSON_UNESCAPED_UNICODE);
        $res = getCurlData($post_url, $post_data, 'post',['Content-Type: application/json']);
        $return_data = json_decode($res,true);
        if (!empty($return_data)&&$return_data['code'] == 200) {
            $trans_data = $this->format_area($return_data['data']);
            $this->success_json($trans_data);

        } else {
            $this->error_json('智能解析失败');


        }


    }

    public function format_area($data)
    {
        if (!empty($data['prov'])) {
            $info = $this->db->select('region_code')->from('region')->where('region_type',1)->where('region_name',$data['prov'])->get()->row_array();
            $data['prov_code'] = empty($info['region_code'])?'':$info['region_code'];

        } else {
            $data['prov_code'] = '';

        }


        if (!empty($data['city'])) {
            $info = $this->db->select('region_code')->from('region')->where('region_type',2)->where('region_name',$data['city'])->get()->row_array();
            $data['city_code'] = empty($info['region_code'])?'':$info['region_code'];

        } else {
            $data['city_code'] = '';

        }


        if (!empty($data['district'])) {
            $info = $this->db->select('region_code')->from('region')->where('region_type',3)->where('region_name',$data['district'])->get()->row_array();
            $data['district_code'] = empty($info['region_code'])?'':$info['region_code'];

        } else {
            $data['district_code'] = '';

        }
        return $data;

    }



    public function get_headerlog(){
        $uid = $this->input->post_get('uid');
        $flag = 'abnormal';
        $this->load->model('Product_model','product_model',false,'product');

        $headerData = $this->abnormal_model->header();
        $searchData = $this->product_model->get_user_search($uid,$flag);
        if(!empty($searchData)){
            $datas = array_column($searchData,'name');
            foreach($headerData as $key=>&$value){

                if(in_array($value['name'],$datas)){

                    $value['status'] =1;
                }
            }
        }

        $this->success_json($headerData);
    }

    public function save_table_list(){
        $req_data = $this->input->post_get('order_initial'); //数组格式
        $type = $this->input->post_get('flag'); //列表类型
        $uid = $this->input->post_get('uid');
        if (empty($type)) $this->error_json('列表类型缺失');
        if (empty($req_data)) $this->error_json('列表数据缺失');
        $req_data = json_decode($req_data,true);
        $res = $this->product_model->save_table_list($req_data, $type,$uid);
        if ($res) {
            $this->success_json([], null, '编辑成功');
        } else {
            $this->error_json('编辑失败');
        }
    }


}