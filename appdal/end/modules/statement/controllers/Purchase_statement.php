<?php
/**
 * 对账单控制器
 * User: Jolon
 * Date: 2020/04/14 10:00
 */

class Purchase_statement extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('statement/Purchase_statement_model');
        $this->load->model('statement/Purchase_inventory_items_model');

    }


    /**
     * 创建对账单(第一步)
     * @author Jolon
     */
    public function create_statement_preview()
    {
        $instock_batchs = $this->input->get_post('instock_batchs');

        if(empty($instock_batchs)){
            // 读取缓存的查询SQL
            $create_statement_order_querySql = $this->rediss->getData(md5(getActiveUserId().'-create_statement_order'));
            if(empty($create_statement_order_querySql)) $this->error_json("缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $create_statement_order_querySql = base64_decode($create_statement_order_querySql);
            $create_statement_order_querySql = str_replace("SELECT *","SELECT a.instock_batch",$create_statement_order_querySql);

            $instock_batchs  = $this->Purchase_statement_model->purchase_db->query($create_statement_order_querySql)->result_array();
            $instock_batchs  = array_column($instock_batchs,'instock_batch');
        }

        if(count($instock_batchs) > 10000) $this->error_json('您操作的数据过多，极耗系统资源，为保证系统稳定，请不要超过1万条数据');
        if(empty($instock_batchs)) $this->error_json('没有获取到待【生成对账单】的数据，请确认操作');

        // 如果生成对账单成功 则只返回 生成成功的对账单号
        // 如果一个对账单都没有生成成功  则 返回错误提示信息
        $inventory_item_list = $this->Purchase_inventory_items_model->get_inventory_items_for_statement(['instock_batch' => $instock_batchs]);
        if($inventory_item_list['code'] == false){
            $this->error_json($inventory_item_list['message']);
        }else{
            $inventory_item_list = $inventory_item_list['data'];
        }


        // 过滤 入库明细记录 是否可以生成对账单，显示错误数据
        $check_valid_result = $this->Purchase_statement_model->check_inventory_item_able_statement($inventory_item_list);
        if(empty($check_valid_result['data_list'])){// 没有有效数据
            $this->error_data_json(['error_list' => $check_valid_result['error_data']], $check_valid_result['message']);
        }else{
            $inventory_item_list = $check_valid_result['data_list'];
        }


        // 按 是否一致 组合条件分组
        $check_same_result = $this->Purchase_statement_model->group_inventory_item_is_same_statement($inventory_item_list);
        $inventory_item_list = $check_same_result['data_list'];


        // 最终要生成对账单的目标数据（排除不符合要求）
        // 是否直接生成
        if(count($inventory_item_list) == 1 and count(current($inventory_item_list))  <= 1500){// 只有一个对账单，进行预览
            // 验证通过的入库批次号
            $instock_batchs = array_column(current($inventory_item_list),'instock_batch');
            $result = $this->Purchase_statement_model->get_statement_format_data($instock_batchs);
            if($result['code']){
                // Start:数据通过验证  缓存验证结果（入库批次 创建对账单（第二步） 调用）
                $instock_batchs = array_values($instock_batchs);
                sort($instock_batchs);
                $this->rediss->setData(md5(implode('_',$instock_batchs)),1,6000);
                // End:数据通过验证  缓存验证结果

                $this->success_json_format($result['data']);
            }else{
                $this->error_json($result['message']);
            }
        }else{
            $return_data = [];
            // 多个对账单 进行拆分，自动生成，不预览
            foreach ($inventory_item_list as $key => $lists_value){
                if(count($lists_value) > 1500){
                    $lists_value_ll = array_chunk($lists_value,1500);
                }else{
                    $lists_value_ll = [$lists_value];
                }

                foreach($lists_value_ll as $ll_value){
                    // 数据验证通过，不存在可以冲销的入库单 -->>> 生成对账单
                    $result = $this->Purchase_statement_model->create_statement(array_column($ll_value,'instock_batch'),false);
                    if($result['code']){
                        $return_data['statement_number_list'][] = is_array($result['data'])?current($result['data']):$result['data'];
                    }else{
                        $return_data['error_list'][] = $result['message'];
                    }
                }
            }

            $this->success_json_format($return_data);
        }
    }

    /**
     * 入库批次 创建对账单（第二步）
     */
    public function create_statement(){
        $instock_batchs = $this->input->get_post('instock_batchs');
        if(empty($instock_batchs)){
            // 读取缓存的查询SQL
            $create_statement_order_querySql = $this->rediss->getData(md5(getActiveUserId().'-create_statement_order'));
            if(empty($create_statement_order_querySql)) $this->error_json("缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $create_statement_order_querySql = base64_decode($create_statement_order_querySql);
            $create_statement_order_querySql = str_replace("SELECT *","SELECT a.instock_batch",$create_statement_order_querySql);

            $instock_batchs  = $this->Purchase_statement_model->purchase_db->query($create_statement_order_querySql)->result_array();
            $instock_batchs  = array_column($instock_batchs,'instock_batch');
        }

        if(count($instock_batchs) > 10000) $this->error_json('您操作的数据过多，极耗系统资源，为保证系统稳定，请不要超过1万条数据');
        if(empty($instock_batchs)) $this->error_json('没有获取到待【生成对账单】的数据，请确认操作');

        // 如果生成对账单成功 则只返回 生成成功的对账单号
        // 如果一个对账单都没有生成成功  则 返回错误提示信息
        $inventory_item_list = $this->Purchase_inventory_items_model->get_inventory_items_for_statement(['instock_batch' => $instock_batchs]);
        if($inventory_item_list['code'] == false){
            $this->error_json($inventory_item_list['message']);
        }else{
            $inventory_item_list = $inventory_item_list['data'];
        }


        // 过滤 入库明细记录 是否可以生成对账单，显示错误数据
        $check_valid_result = $this->Purchase_statement_model->check_inventory_item_able_statement($inventory_item_list);
        if(empty($check_valid_result['data_list'])){// 没有有效数据
            $this->error_json('数据未通过验证，没有有效数据');
        }else{
            $inventory_item_list = $check_valid_result['data_list'];
        }

        $instock_batchs = array_column($inventory_item_list,'instock_batch');

        // 数据通过验证  缓存验证结果
        $instock_batchs = array_values($instock_batchs);
        sort($instock_batchs);
        $exists = $this->rediss->getData(md5(implode('_',$instock_batchs)));

        if($exists){
            // 数据验证通过，不存在可以冲销的入库单 -->>> 生成对账单
            $result = $this->Purchase_statement_model->create_statement($instock_batchs);
            if($result['code']){
                $this->success_json($result['data']);
            }else{
                $this->error_json($result['message']);
            }
        }else{
            $this->error_json('数据未通过验证，请重新创建对账单');
        }

    }

    public function get_search_params(){
        $params = [];

        $params['ids'] = $this->input->get_post('ids');
        $params['order_number'] = $this->input->get_post('order_number');
        $params['purchase_number'] = $this->input->get_post('purchase_number');
        $params['create_user_id'] = $this->input->get_post('create_user_id');
        $params['buyer_id'] = $this->input->get_post('buyer_id');
        $params['supplier_code'] = $this->input->get_post('supplier_code');
        $params['statement_pdf_status'] = $this->input->get_post('statement_pdf_status');
        $params['supplier_is_gateway'] = $this->input->get_post('supplier_is_gateway');
        $params['is_drawback'] = $this->input->get_post('is_drawback');
        $params['purchase_name'] = $this->input->get_post('purchase_name');
        $params['demand_number'] = $this->input->get_post('demand_number');
        $params['status_valid'] = $this->input->get_post('status_valid');
        $params['statement_pay_status'] = $this->input->get_post('statement_pay_status');
        $params['create_time_start'] = $this->input->get_post('create_time_start');
        $params['create_time_end'] = $this->input->get_post('create_time_end');
        $params['is_purchasing'] = $this->input->get_post('is_purchasing');
        $params['group_ids']     =  $this->input->get_post('group_ids'); // 组别ID
        $params['free_shipping']     =  $this->input->get_post('free_shipping'); // 是否包邮
        $params['create_user_name'] = $this->input->get_post('create_user_name');
        $params['status']     =  $this->input->get_post('status'); // 对账单状态
        $params['source_party']     =  $this->input->get_post('source_party'); // 对账单来源
        $params['statement_user_id']     =  $this->input->get_post('statement_user_id'); // 对账人
        $params['statement_user_name'] = $this->input->get_post('statement_user_name');
        $params['instock_month']     =  $this->input->get_post('instock_month'); // 入库月份
        $params['accout_period_time_start'] = $this->input->get_post('accout_period_time_start');// 应付款时间-开始
        $params['accout_period_time_end'] = $this->input->get_post('accout_period_time_end');// 应付款时间-结束

        return $params;
    }

    /**
     * 对账单管理列表
     */
    public function get_statement_list(){
        $params = $this->get_search_params();
        $page_data = $this->format_page_data();
        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }


        $result                       = $this->Purchase_statement_model->get_statement_list($params, $page_data['offset'], $page_data['limit'], $page_data['page']);
        $result['data_list']['value'] = $this->Purchase_statement_model->format_compact_list($result['data_list']['value']);
        $this->success_json($result['data_list'], $result['page_data']);

    }


    /**
     * 【作废】设置 对账单 是否有效
     */
    public function set_status_valid(){
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number)) $this->error_json('参数错误');

        $result = $this->Purchase_statement_model->set_status_valid($statement_number);
        if($result['code']){
            $this->success_json([],null,$result['message']);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 打印对账单
     */
    public function print_statement(){
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number)) $this->error_json('参数错误');

        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        try{
            $statement = $this->Purchase_statement_model->get_statement($statement_number);
            if($statement){
                $statement       = format_price_multi_floatval($statement);

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
     * 查看对账单详情
     */
    public function preview_statement_detail(){
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number)) $this->error_json('参数错误');

        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('finance/Purchase_order_pay_model');
        $this->load->model('supplier/Supplier_model');

        $statement = $this->Purchase_statement_model->get_statement($statement_number);
        if(empty($statement)){
            $this->error_json('未获取到数据');
        }

        // 供应商是否包邮
        $supplierInfo = $this->Supplier_model->get_supplier_info($statement['supplier_code']);
        $statement['is_postage']    = $supplierInfo['is_postage'];
        $statement['is_postage_cn'] = getIsPostage($supplierInfo['is_postage']);

        foreach($statement['summary_list'] as $key_summary => $value_summary){
            // 已付金额
            $paid_price_list = $this->Purchase_order_pay_model->get_pay_total_by_purchase_number([$value_summary['purchase_number']]);

            if($paid_price_list and isset($paid_price_list[0])){
                $statement['summary_list'][$key_summary]['paid_product_money'] = $paid_price_list[0]['paid_product_money'];
                $statement['summary_list'][$key_summary]['paid_freight']       = $paid_price_list[0]['paid_freight'];
                $statement['summary_list'][$key_summary]['paid_discount']      = $paid_price_list[0]['paid_discount'];
                $statement['summary_list'][$key_summary]['paid_process_cost']  = $paid_price_list[0]['paid_process_cost'];
                $statement['summary_list'][$key_summary]['paid_real_price']    = $paid_price_list[0]['paid_real_price'];
            }else{
                $statement['summary_list'][$key_summary]['paid_product_money'] = 0;
                $statement['summary_list'][$key_summary]['paid_freight']       = 0;
                $statement['summary_list'][$key_summary]['paid_discount']      = 0;
                $statement['summary_list'][$key_summary]['paid_process_cost']  = 0;
                $statement['summary_list'][$key_summary]['paid_real_price']    = 0;
            }
        }

        //付款记录
        $pay_record_list = $this->Purchase_order_pay_model->get_pay_records_by_pur_number($statement_number);
        $pay_record_list_tmp = [];
        $statement_first_paid_time = '';
        if(!empty($pay_record_list)){
            foreach($pay_record_list as $k => $v){
                $pay_record_list[$k]['pay_status']=getPayStatus($v['pay_status']);
                if(empty($v['payer_id'])){
                    $pay_record_list[$k]['payer_id'] ='';
                }else{
                    $pay_record_list[$k]['payer_id']=getUserNameById($v['payer_id']);
                }
                if($v['requisition_method']==4){//合同列表、合同详情中的调整
                    $pay_record_list[$k]['js_ratio']=0;
                }
                if($v['pay_status'] == PAY_PAID){
                    $statement_first_paid_time = $v['payer_time'];
                }

                $pay_record_tmp = [
                    'id'                 => $v['id'],
                    'requisition_number' => $v['requisition_number'],
                    'requisition_method' => $v['requisition_method'],
                    'loss_product_money' => $statement['total_loss_product_money'],// 请款报损商品额=对账单里面的报损商品额，只会全部付款，每次都相同
                    'product_money'      => $v['product_money'],
                    'freight'            => $v['freight'],
                    'discount'           => $v['discount'],
                    'process_cost'       => $v['process_cost'],
                    'commission'         => $v['commission'],
                    'commission_percent' => $v['commission_percent'],
                    'pay_price'          => $v['pay_price'],
                    'pay_status'         => getPayStatus($v['pay_status']),
                    'pur_number'         => $v['pur_number'],
                    'applicant'          => getUserNameById($v['applicant']),
                    'application_time'   => $v['application_time'],
                    'freight_desc'       => $v['freight_desc'],
                    'create_notice'      => $v['create_notice'],
                    'compact_url'        => $v['compact_url'],
                ];

                $auditor_user = $audit_time = $audit_notice = '';
                // 取最新一个审核人
                if(!empty($v['general_manager_id'])){
                    $auditor_user = $v['general_manager_id'];
                    $audit_time = $v['general_manager_time'];
                    $audit_notice = $v['general_manager_notice'];
                }elseif(!empty($v['financial_officer_id'])){
                    $auditor_user = $v['financial_officer_id'];
                    $audit_time = $v['financial_officer_time'];
                    $audit_notice = $v['financial_officer_notice'];
                }elseif(!empty($v['financial_manager_id'])){
                    $auditor_user = $v['financial_manager_id'];
                    $audit_time = $v['financial_manager_time'];
                    $audit_notice = $v['financial_manager_notice'];
                }elseif(!empty($v['financial_supervisor_id'])){
                    $auditor_user = $v['financial_supervisor_id'];
                    $audit_time = $v['financial_supervisor_time'];
                    $audit_notice = $v['financial_supervisor_notice'];
                }elseif(!empty($v['approver'])){
                    $auditor_user = $v['approver'];
                    $audit_time = $v['processing_time'];
                    $audit_notice = $v['processing_notice'];
                }elseif(!empty($v['waiting_id'])){
                    $auditor_user = $v['waiting_id'];
                    $audit_time = $v['waiting_time'];
                    $audit_notice = $v['waiting_notice'];
                }elseif(!empty($v['auditor'])){
                    $auditor_user = $v['auditor'];
                    $audit_time = $v['review_time'];
                    $audit_notice = $v['review_notice'];
                }
                $pay_record_tmp['auditor_user'] = getUserNameById($auditor_user);
                $pay_record_tmp['audit_time'] = $audit_time ;
                $pay_record_tmp['audit_notice'] = $audit_notice;
                $pay_record_tmp['is_postage_cn'] = $statement['is_postage_cn'];

                $pay_record_list_tmp[] = $pay_record_tmp;
            }
        }


        // 删除"采购单明细"的子页面
        // 采购单明细、采购单冲销汇总
        //$statement += $this->Purchase_statement_model->get_statement_append_info(array_column($statement['summary_list'],'purchase_number'));

        $statement['pay_record_list']                 = $pay_record_list_tmp;
        $statement['statement_first_paid_time']       = $statement_first_paid_time;

        $this->success_json_format($statement);
    }

    /**
     * 上传对账单 PDF 文件
     */
    public function upload_statement_pdf(){
        $statement_number = $this->input->get_post('statement_number');
        $pdf_file_name    = $this->input->get_post('pdf_file_name');
        $pdf_url          = $this->input->get_post('pdf_url');

        if(empty($statement_number)) $this->error_json('参数错误');
        if(empty($pdf_file_name) or empty($pdf_url)) $this->error_json('扫描件文件名或文件路径缺失');

        $statement = $this->Purchase_statement_model->get_statement($statement_number,false);
        if(empty($statement)) $this->error_json('对账单号非法');

        // 保存合同扫描件
        $result = $this->Purchase_statement_model->upload_statement_pdf($statement_number,$pdf_file_name,$pdf_url);
        if($result['code']){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 上传对账单扫描件
     * @author Jolon 2018/01/09
     * /statement/purchase_statement/batch_upload_statement_pdf
     */
    public function batch_upload_statement_pdf(){
        $this->load->library('Filedirdeal');
        $this->load->library('Upload_image');
        $this->load->model('statement/Purchase_statement_model');

        $zip_file_name = $this->input->get_post('zip_file_name');
        $zip_file_path = $this->input->get_post('zip_file_path');


        $sub_dir_name = explode('.',$zip_file_name);
        $zip_filePath = explode('.',$zip_file_path);
        if(empty($zip_filePath) or !isset($zip_filePath[1]) or $zip_filePath[1] != 'zip'){
            $this->error_json('所传的压缩包必须是zip格式');
        }

        $ext = $zip_filePath[1];
        $out_filePath = $zip_filePath[0];
        if(!file_exists($out_filePath)){
            mkdir($out_filePath,0777,true);
        }

        $cache_file_name = [];// 缓存中文文件名（中文文件名存在乱码，通过变量来保存）

        $zip = new ZipArchive();
        $openRes = $zip->open($zip_file_path);
        if ($openRes !== true) {
            $this->error_json('压缩包文件解压失败');
        }

        $fileNum = $zip->numFiles;
        for ($i = 0; $i < $fileNum; $i++) {
            $statInfo = $zip->statIndex($i, ZipArchive::FL_ENC_RAW);
            $cn_file_name = iconv("GBK",'utf-8//IGNORE',$statInfo['name']);

            $file_name = explode('.',$statInfo['name']);
            if(empty($file_name) or !isset($file_name[0]) or !isset($file_name[1])){
                $this->error_json('压缩包文件内容文件格式存在错误');
                break;
            }

            $md5_file_name = md5($file_name[0]);
            $cache_file_name[$md5_file_name] = $cn_file_name;
            $zip->renameIndex($i, iconv("GBK",'utf-8//IGNORE',$md5_file_name.'.'.$file_name[1]));
        }
        $zip->close();
        $zip->open($zip_file_path);
        $zip->extractTo($out_filePath);
        $zip->close();
        unlink($zip_file_path);

        $success_list = $error_list = [];

        $fileList = $this->filedirdeal->readAllFile($out_filePath);
        if(count($fileList) != count($cache_file_name)){
            $this->error_json('数据缓存失败');
        }

        if($fileList){
            foreach($fileList as $value_path){
                $now_file_name = basename($value_path);
                $file_name = explode('.',$now_file_name);
                $file_name = $file_name[0];

                $begin_now_file_name = isset($cache_file_name[$file_name])?$cache_file_name[$file_name]:'';

                if(empty($begin_now_file_name)){
                    $this->error_json('数据缓存失败');
                }

                preg_match('/[A-Za-z0-9\-]{6,}/',$begin_now_file_name,$match);
                if(!isset($match[0]) or empty($match[0])){
                    $error_list[$begin_now_file_name] = '命名不符合';
                    continue;
                }
                $statement_number = $match[0];
                $statement = $this->Purchase_statement_model->get_statement($statement_number,false);
                if(empty($statement)){
                    $error_list[$begin_now_file_name] = '对账单号不存在';
                    continue;
                }
                if(!in_array($statement['statement_pay_status'],[PAY_UNPAID_STATUS,
                    PAY_SOA_REJECT,
                    PAY_MANAGER_REJECT,
                    PAY_WAITING_MANAGER_REJECT,
                    PAY_FINANCE_REJECT,
                    PAY_REJECT_SUPERVISOR,
                    PAY_REJECT_MANAGER,
                    PAY_REJECT_SUPPLY,
                    PAY_GENERAL_MANAGER_REJECT])){
                    $error_list[$begin_now_file_name] = '付款状态不符合';
                    continue;
                }
                // 验证文件名是否符合规则
                if(stripos($begin_now_file_name,$statement_number) !== 0){
                    $error_list[$begin_now_file_name] = '上传的对账单扫描件文件名必须以对账单号开头';
                    continue;
                }

                $value_path_sub = substr($value_path,stripos($value_path,'download_csv/statement_pdf_upload'));
                $java_file_path = SECURITY_PUR_BIG_FILE_PATH.'/'.$value_path_sub;// 本地服务器文件地址

                //$java_result = $this->upload_image->doUploadFastDfs('image',$value_path,false);
                if($java_file_path){
                    $result = $this->Purchase_statement_model->upload_statement_pdf($statement_number,$begin_now_file_name,$java_file_path);
                    if($result['code']){
                        $success_list[$begin_now_file_name] = $result['message'];
                    }else{
                        $error_list[$begin_now_file_name] = $result['message'];
                    }
                }else{
                    $error_list[$begin_now_file_name] = '合同扫描件文件上传JAVA服务器失败';
                    continue;
                }
            }

            $this->success_json(['success_list' => $success_list,'error_list' => $error_list]);

        }else{
            $this->error_json("压缩包解压后的文件夹为空：".$out_filePath);
        }

    }

    /**
     * 下单对账单 EXCEL 文件
     */
    public function download_statement_html(){
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number)) $this->error_json('参数错误');

        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        try{
            $statement = $this->Purchase_statement_model->get_statement($statement_number);
            if($statement){
                $statement       = format_price_multi_floatval($statement);

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
            trigger_error($e->getMessage());
        }

    }

    /**
     * 下单对账单 EXCEL 文件
     */
    public function download_statement_excel(){
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number)) $this->error_json('参数错误');

        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        try{
            $statement = $this->Purchase_statement_model->get_statement($statement_number);
            if($statement){
                $statement       = format_price_multi_floatval($statement);

                $key             = "print_statement_tmp";//缓存键
                $print_statement = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_statement_excel');
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
     * 下载对账单运费明细 EXCEL
     * @author Jolon
     */
    public function download_freight_details()
    {

        $statement_number = $this->input->get_post('statement_number');
        if (empty($statement_number)) $this->error_json('参数错误');

        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('supplier/supplier_model');

        try {
            $statement = $this->Purchase_statement_model->get_statement($statement_number);
            if(empty($statement)) $this->error_json('单据不存在');

            $supplierInfo = $this->supplier_model->get_supplier_info($statement['supplier_code'],false);

            $history_statement_list = [];
            // 不包邮时判断是否重复多次生产对账单，不显示运费 明细
            if($supplierInfo and $supplierInfo['is_postage'] == 2){// 2.不包邮
                $history_statement_list = $this->Purchase_statement_model->getHistoryStatementNumber(array_column($statement['summary_list'],'purchase_number'),$statement['create_time']);
            }

            $filePath = $this->Purchase_statement_model->download_freight_details_html($statement,$history_statement_list);

            if ($filePath) {
                $this->success_json(['filePath' => $filePath, 'key' => '']);
            } else {
                $this->error_json('模板数据生成失败');
            }
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }

    }

    /**
     * 采购确认扫描件
     * /statement/purchase_statement/confirm_statement_pdf
     * @author Justin
     */
    public function confirm_statement_pdf()
    {
        $statement_number = $this->input->get_post('statement_number');
        $audit_status = $this->input->get_post('audit_status');//审核状态：agree-审核通过，disagree-驳回
        $audit_remark = $this->input->get_post('audit_remark');//审核备注

        if (empty($statement_number) or !in_array($audit_status, ['agree', 'disagree'])) $this->error_json('参数错误');

        if ('disagree' == $audit_status && empty($audit_remark)) {
            $this->error_json('驳回时请填写备注');
        }

        //采购确认合同扫描件
        $result = $this->Purchase_statement_model->confirm_statement_pdf($statement_number, $audit_status, $audit_remark);
        if ($result['code']) {
            $this->success_json($result['data']);
        } else {
            $this->error_json($result['message']);
        }
    }

    /**
     * 查看上传pdf操作日志
     * /statement/purchase_statement/get_operation_pdf_logs
     */
    public function get_operation_pdf_logs()
    {
        $statement_number = $this->input->get_post('statement_number');
        $this->load->model('statement/Purchase_statement_note_model');
        $logs = $this->Purchase_statement_note_model->get_remark_list($statement_number,1);
        $this->success_json($logs);
    }


    /**
     * 下载付款申请书
     */
    public function get_statement_pay_requisition(){
        $ids = $this->input->get_post('ids');
        $result = $this->Purchase_statement_model->get_statement_pay_requisition($ids);
        if($result === false){
            $this->error_json('数据获取失败，请核对数据后操作');
        }else{
            $this->success_json($result);
        }
    }

    /**
     * 批量下载对账单
     */
    public function batch_download_statement(){
        set_time_limit(0);
        $params = $this->get_search_params();
        file_put_contents(get_export_path('test_log').'log_20201127.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $total = $this->Purchase_statement_model->get_statement_list($params, $offsets, $limit,$page,false,'sum');

        $this->load->model('system/Data_control_config_model');
        try {
            file_put_contents(get_export_path('test_log').'log_20201127.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
            $result = $this->Data_control_config_model->insertDownData($params, 'STATEMENT_PDF_EXPORT', '对账单PDF下载', getActiveUserName(), 'zip', $total);
        } catch (Exception $exp) {
            file_put_contents(get_export_path('test_log').'log_20201127.txt','END '.$exp->getMessage().PHP_EOL,FILE_APPEND);
            $this->error_json($exp->getMessage());
        }
        file_put_contents(get_export_path('test_log').'log_20201127.txt','END '.json_encode($result).PHP_EOL,FILE_APPEND);
        if ($result) {
            $this->success_json([], '',"已添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }
        exit;
    }


    /**
     * 对账单CSV导出
     */
    public function statement_export_csv(){
        set_time_limit(0);
        $params = $this->get_search_params();
        file_put_contents(get_export_path('test_log').'log_20210106.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $total = $this->Purchase_statement_model->get_statement_list($params, $offsets, $limit,$page,false,'sum');

        $this->load->model('system/Data_control_config_model');
        try {
            file_put_contents(get_export_path('test_log').'log_20210106.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
            $result = $this->Data_control_config_model->insertDownData($params, 'STATEMENT_EXPORT_CSV', '对账单CSV下载', getActiveUserName(), 'csv', $total);
        } catch (Exception $exp) {
            file_put_contents(get_export_path('test_log').'log_20210106.txt','END '.$exp->getMessage().PHP_EOL,FILE_APPEND);
            $this->error_json($exp->getMessage());
        }
        file_put_contents(get_export_path('test_log').'log_20210106.txt','END '.json_encode($result).PHP_EOL,FILE_APPEND);
        if ($result) {
            $this->success_json([], '',"已添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }
        exit;
    }


    //region e签宝电子合同签署流程
    /**
     * 甲方先盖章（甲方发起盖章）
     */
    public function a_initiator_start_flow(){
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number) or !is_string($statement_number)){
            $this->error_json('statement_number 参数不符合要求');
        }

        $result = $this->Purchase_statement_model->initiator_start_flow($statement_number);

        if($result['code'] == true){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 甲方对账人审核
     */
    public function a_statement_audit(){
        $statement_number = $this->input->get_post('statement_number');

        $statementInfo = $this->Purchase_statement_model->get_statement($statement_number,false);
        if($statementInfo['statement_user_id'] != getActiveUserId()){
            $this->error_json('只有对账人才能审核该对账单！');
        }

        $result = $this->Purchase_statement_model->signfields_flow($statement_number);
        if($result['code'] == true){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 甲方盖章
     */
    public function a_signfields_flow(){
        $statement_number_list = $this->input->get_post('statement_number_list');

        $operatorMsg = [];
        foreach($statement_number_list as $statement_number){
            $result = $this->Purchase_statement_model->signfields_flow($statement_number);

            if($result['code'] == true){
                $operatorMsg['success_list'][$statement_number] = $result['data'];
            }else{
                $operatorMsg['error_list'][$statement_number] = $result['message'];
            }
        }

        $this->success_json($operatorMsg);
    }


    /**
     * 甲方催办
     */
    public function a_signflows_rushsign(){
        $statement_number_list = $this->input->get_post('statement_number_list');

        $operatorMsg = [];
        foreach($statement_number_list as $statement_number){
            $result = $this->Purchase_statement_model->signflows_rushsign($statement_number);

            if($result['code'] == true){
                $operatorMsg['success_list'][$statement_number] = '催办成功';
            }else{
                $operatorMsg['error_list'][$statement_number] = $result['message'];
            }
        }

        $this->success_json($operatorMsg);
    }

    /**
     * 甲方撤销
     */
    public function a_signflows_revoke(){
        $statement_number_list = $this->input->get_post('statement_number_list');

        $operatorMsg = [];
        foreach($statement_number_list as $statement_number){
            $result = $this->Purchase_statement_model->signflows_revoke($statement_number);

            if($result['code'] == true){
                $operatorMsg['success_list'][$statement_number] = '撤销成功';
            }else{
                $operatorMsg['error_list'][$statement_number] = $result['message'];
            }
        }

        $this->success_json($operatorMsg);
    }



    /**
     * 上传附属文件
     */
    public function upload_attachment_pdf(){
        $statement_number = $this->input->get_post('statement_number');
        $attachment_pdf_path = $this->input->get_post('attachment_pdf_path');

        if(empty($statement_number) or empty($attachment_pdf_path)){
            $this->error_json('必要参数缺失');
        }

        $result = $this->Purchase_statement_model->upload_attachment_pdf($statement_number,$attachment_pdf_path);

        if($result['code'] == true){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    //endregion
}