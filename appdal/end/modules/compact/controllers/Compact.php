<?php
/**
 * 合同控制器
 * User: Jaxton
 * Date: 2019/01/08 10:00
 */

class Compact extends MY_Controller{
	public function __construct(){
        parent::__construct();
        $this->load->model('Compact_list_model','compact_model');
        $this->load->model('supplier_joint_model');
    }

    /**
     * 根据合同ID获取合同信息
     */
    public function get_compact_by_id(){
        $compact_id = $this->input->get_post('compact_id');//合同ID
        $result = $this->compact_model->get_compact_by_id($compact_id);
        $this->success_json($result);
    }


    /**
     * 获取搜索条件
     */
    public function get_search_params(){
        $params    = [
            'compact_number'    => $this->input->get_post('compact_number'),//合同号
            'create_user_id'    => $this->input->get_post('create_user_id'),//创建人
            'supplier_code'     => $this->input->get_post('supplier_code'),//供应商
            'create_time_start' => $this->input->get_post('create_time_start'),//创建时间开始
            'create_time_end'   => $this->input->get_post('create_time_end'), //创建时间截止
            'statement_number'  => $this->input->get_post('statement_number'), //对账单号
            'payment_status'    => $this->input->get_post('payment_status'), //付款状态
            'is_file_uploaded'  => $this->input->get_post('is_file_uploaded'), //是否上传扫描件
            'payment_date_start'=> $this->input->get_post('payment_date_start'),  //付款时间-开始
            'payment_date_end'  => $this->input->get_post('payment_date_end'),  //付款时间-结束
            'purchase_type'     => $this->input->get_post('purchase_type'),  //业务线
            'is_gateway'        => $this->input->get_post('is_gateway'),  // 合同是否对接门户
            'is_drawback'       => $this->input->get_post('is_drawback'),  // 是否退税
            'group_ids'         => $this->input->get_post('group_ids'),
            'settlement_method' => $this->input->get_post('settlement_method'),//结算方式
            'file_upload_time_start' => $this->input->get_post('file_upload_time_start'),//最新扫描件时间开始
            'file_upload_time_end'   => $this->input->get_post('file_upload_time_end'), //最新扫描件时间截止
            'free_shipping'     => $this->input->get_post('free_shipping'), // 是否包邮
        ];
        return $params;
    }

    /**
     * 获取合同列表
     * /compact/compact/get_compact
     * @author Jaxton 2018/01/08
     */
    public function get_compact(){
        $params= $this->get_search_params();
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

        $result                       = $this->compact_model->get_compact_list($params, $page_data['offset'], $page_data['limit'], $page_data['page']);
        
        $result['data_list']['value'] = $this->compact_model->formart_compact_list($result['data_list']['value']);
        $this->success_json($result['data_list'], $result['page_data']);
    }

    /**
    * 获取合同详情
    * /compact/compact/get_compact_detail
    * @author Jaxton 2018/01/08
    */
    public function get_compact_detail(){
    	$compact_number=$this->input->get_post('compact_number');
    	if(empty($compact_number)){
            $this->error_json('缺少合同编号');
    	}
    	$result=$this->compact_model->get_compact_detail($compact_number);
        $this->success_json_format($result);
    }

    /**
    * 获取付款申请书数据
    * /compact/compact/get_pay_requisition
    * @author Jaxton 2019/02/21
    */
    public function get_pay_requisition(){
        $compact_number=$this->input->get_post('compact_number');
        $requisition_number=$this->input->get_post('requisition_number');
        if(empty($compact_number) || empty($requisition_number)){
            $this->error_json('合同编号和请款单号不能缺少');
        }
        try{
            $result=$this->compact_model->get_pay_requisition($compact_number,$requisition_number);

            if($result['success'] && $result['data']){
                $data= urlencode(json_encode($result['data'],JSON_UNESCAPED_UNICODE));
                if(is_array($data)){
                    throw new Exception('data is array:' . print_r($result, true));
                }
                $print_data = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_payment_apply');
                if(is_array($print_data)){
                    throw new Exception('$print_data is array:' . print_r($print_data, true));
                }
                $url           = $print_data."?data=".$data;
                $html = file_get_contents($url);
                $this->success_json($html);
            }else{
                $this->error_json('未获取到数据');
            }
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }

    }
    /**
     * 查看付款申请书数据
     * /compact/compact/pay_requisition_see
     * @author harvin 2019-3-20
     */
    public function pay_requisition_see(){
          $compact_number=$this->input->get_post('compact_number');
          $requisition_number=$this->input->get_post('requisition_number');
          if(empty($compact_number) || empty($requisition_number)){
              $this->error_json('合同编号和请款单号不能缺少');
          }
          try{
            $result=$this->compact_model->get_pay_requisition($compact_number,$requisition_number);
            if($result['success'] && $result['data']){
                $this->success_json($result);
            }else{
                $this->error_json('未获取到数据');
            }
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }
    }




    /**
    * 获取付款回单
    * /compact/compact/get_pay_receipt
    * @author Jaxton 2019/02/21
    */
    public function get_pay_receipt(){
        $compact_number=$this->input->get_post('compact_number');
        $requisition_number=$this->input->get_post('requisition_number');
        if(empty($compact_number) || empty($requisition_number)){
            $this->error_json('合同编号和请款单号不能缺少');
        }
        $result=$this->compact_model->get_pay_receipt($compact_number,$requisition_number);
        if($result['success']){
            $this->success_json(['file'=>$result['data']]);
        }else{
            $this->error_json('未查询到数据');
        }
    }

    /**
     * 上传合同扫描件
     * @author Jaxton 2018/01/09
     * /compact/compact/upload_compact_file
     */
    public function upload_compact_file(){
        //print_r($_FILES);die;
        $pop_id           = $this->input->get_post('pop_id');//请款单ID
        $pc_id            = $this->input->get_post('pc_id');//合同ID

        if(empty($pc_id)) $this->error_json('缺少参数合同ID');
        if(empty($pop_id)) $this->error_json('缺少参数请款单ID');

        if(empty($_FILES['compact_file']['name'])){

            $this->error_json('请选择文件上传');
        }else{
            $file_name   = $_FILES['compact_file']['name'];
            $upload_file = $_FILES['compact_file'];
        }

        // 验证合同是否存在
        $compactInfo = $this->compact_model->get_compact_by_id($pc_id);
        if(empty($compactInfo) or empty($compactInfo['compact_number'])) $this->error_json('合同不存在');

        // 验证文件名是否符合规则
        if(stripos($file_name,$compactInfo['compact_number']) !== 0){
            $this->error_json('上传的合同扫描件文件名必须以合同号开头');
        }

        // 验证 合同是否允许上传合同扫描件
        $is_allow_upload_file = $this->compact_model->isAllowUploadFile($compactInfo['compact_number']);
        if($is_allow_upload_file === false) $this->error_json('该合同禁止上传合同扫描件');

        $result = $this->compact_model->upload_compact_file($pop_id, $pc_id, $upload_file);
        //print_r($result);
        if($result['success']){
            $this->success_json([], null, '操作成功');
        }else{
            $this->error_json('操作失败');
        }
    }

    /**
    * 保存上传合同扫描件路径
    * @author Jaxton 2018/02/14
    * /compact/compact/upload_compact_file_conserve
    */
    public function upload_compact_file_conserve(){
        $pop_id           = $this->input->get_post('pop_id');//请款单ID
        $pc_id            = $this->input->get_post('pc_id');//合同ID
        $file_info        = $this->input->get_post('file_info');//文件信息

        if(empty($pc_id)) $this->error_json('缺少参数合同ID');
        if(empty($file_info['file_name'])) $this->error_json('上传的文件名缺失');


        // 验证合同是否存在
        $compactInfo = $this->compact_model->get_compact_by_id($pc_id);
        if(empty($compactInfo) or empty($compactInfo['compact_number'])) $this->error_json('合同不存在');

        // 验证文件名是否符合规则
        if(stripos($file_info['file_name'],$compactInfo['compact_number']) !== 0){
            $this->error_json('上传的合同扫描件文件名必须以合同号开头');
        }

        // 验证 合同是否允许上传合同扫描件
        $is_allow_upload_file = $this->compact_model->isAllowUploadFile($compactInfo['compact_number']);
        if($is_allow_upload_file === false) $this->error_json('该合同禁止上传合同扫描件');

        $result=$this->compact_model->upload_compact_file_conserve($pop_id,$pc_id,$file_info,true);
        if($result['success']){
            $this->success_json([],null,$result['message']);
        }else{
            $this->error_json($result['message']);
        }
    }


    /**
     * 上传原始合同扫描件
     * @author Jolon 2018/01/09
     * /compact/compact/upload_compact_original_scan_file
     */
    public function upload_compact_original_scan_file(){
        $compact_number = $this->input->get_post('compact_number');//合同单号
        $file_name      = $this->input->get_post('file_name');//文件名
        $file_path      = $this->input->get_post('file_path');//文件路径

        if(empty($compact_number)) $this->error_json('缺少参数合同单号缺失');
        if(empty($file_name)) $this->error_json('上传的文件名缺失');
        if(empty($file_path)) $this->error_json('上传的文件路径缺失');


        // 验证文件名是否符合规则
        if(stripos($file_name,$compact_number) !== 0){
            $this->error_json('上传的合同扫描件文件名必须以合同号开头');
        }

        // 验证 合同是否允许上传合同扫描件
        $is_allow_upload_file = $this->compact_model->isAllowUploadFile($compact_number);
        if($is_allow_upload_file === false) $this->error_json('该合同禁止上传合同扫描件');

        $result = $this->compact_model->upload_compact_original_scan_file($compact_number,$file_name,$file_path);

        if($result['success']){

            $this->supplier_joint_model->pushSmcCompactStatusData($compact_number, 2);

            $this->success_json([], null, $result['message']);
        }else{
            $this->error_json($result['message']);
        }
    }



    /**
     * 上传原始合同扫描件
     * @author Jolon 2018/01/09
     * /compact/compact/upload_compact_original_scan_file
     */
    public function batch_upload_compact_original_scan_file(){
        $this->load->library('Filedirdeal');
        $this->load->library('Upload_image');
        $this->load->model('Compact_model','Compact_model_new');

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
                $compact_number = $match[0];
                $compactInfo = $this->Compact_model_new->get_compact_one($compact_number,false);
                if(empty($compactInfo)){
                    $error_list[$begin_now_file_name] = '合同号不存在';
                    continue;
                }

                if(!in_array($compactInfo['payment_status'],[PAY_UNPAID_STATUS,
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
                if(stripos($begin_now_file_name,$compact_number) !== 0){
                    $error_list[$begin_now_file_name] = '上传的合同扫描件文件名必须以合同号开头';
                    continue;
                }

                $value_path_sub = substr($value_path,stripos($value_path,'download_csv/compact_original_cache'));
                $java_file_path = SECURITY_PUR_BIG_FILE_PATH.'/'.$value_path_sub;// 本地服务器文件地址

                //$java_result = $this->upload_image->doUploadFastDfs('image',$value_path,false);
                if($java_file_path){
                    $result = $this->compact_model->upload_compact_original_scan_file($compact_number,$begin_now_file_name,$java_file_path);

                    if($result['success']){
                        $this->supplier_joint_model->pushSmcCompactStatusData($compact_number, 2);
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
    * 查看文件
    * @author Jaxton 2018/01/10
    * /compact/compact/see_compact_file
    */
    public function see_compact_file(){
    	$pop_id=$this->input->get_post('pop_id');
    	$pc_id=$this->input->get_post('pc_id');
    	if(empty($pop_id) || empty($pc_id)){
            $this->error_json('缺少ID');
    	}
        $this->load->model('compact/Compact_file_model');
        $result=$this->Compact_file_model->see_compact_file($pop_id,$pc_id);
    	$file_data=[];
        if($result){
            $file_data['file_path'] = is_array($result)?array_column($result,'file_path'):[];
            $this->success_json($file_data);
        }else{
            $this->error_json('获取文件失败');
        }
    }


    /**
     * 下载文件
     * @author Jaxton 2018/01/10
     * /compact/compact/download_compact_file
     */
    public function download_compact_file(){
        $this->load->library('file_operation');
        $pop_id  = $this->input->get_post('pop_id');// 请款单ID
        $pc_id   = $this->input->get_post('pc_id');// 合同ID
        $type_id = $this->input->get_post('type');// 单据类型 2.付款回单
        if(empty($pop_id) || empty($pc_id)){
            $this->error_json('缺少ID');
        }
        $this->load->model('compact/Compact_file_model');
        $result=$this->Compact_file_model->see_compact_file($pop_id,$pc_id);
        if($result){
            if(isset($result[0])) $result = $result[0];
            if(isset($result['file_path']) and $result['file_path']){
                if(strpos($result['file_path'], 'http') === false){
                    $file = UPLOAD_DOMAIN.$result['file_path'];
                }else{
                    $file = $result['file_path'];
                }
                if(!$this->file_operation->download_file($file)){
                    $this->error_json('获取文件失败');
                };
            }else{
                $this->error_json('获取文件失败');
            }
        }else{
            $this->error_json('此文件不存在');
        }


    }

    /**
    * 获取需要下载的文件
    * @author Jaxton 2018/02/01
    * /compact/compact/get_download_compact_file
    */
    public function get_download_compact_file(){
        $pop_id=$this->input->get_post('pop_id');
        $pc_id=$this->input->get_post('pc_id');
        $compact_number=$this->input->get_post('compact_number');
        $requisition_number=$this->input->get_post('requisition_number');
        $type=$this->input->get_post('type');

        if($type == 4){
            if(empty($compact_number)){
                $this->error_json('缺少compact_number');
            }
        }else{
            if(empty($pop_id) || empty($pc_id)){
                $this->error_json('缺少ID');
            }
        }
        if(stripos($compact_number,'-HT') !== false){// 合同单
            $this->load->model('compact/Compact_file_model');
            $result=$this->Compact_file_model->see_compact_file($pop_id,$pc_id);
        }else{// 对账单
            $result = [];
            if($type == 4){
                $this->load->model('statement/Purchase_statement_model');
                $statementInfo = $this->Purchase_statement_model->get_statement($compact_number,false);
                if($statementInfo){
                    $path = !empty($statementInfo['attachmentPathEsign'])?$statementInfo['attachmentPathEsign']:$statementInfo['attachmentPath'];
                    if($path){
                        $result[] = [
                            'file_name' => $compact_number . '运费明细.pdf',
                            'file_path' => $path
                        ];
                    }
                }
            }else{
                $this->load->model('finance/Purchase_order_pay_model');
                $payInfo = $this->Purchase_order_pay_model->get_pay_records_by_requisition_number($requisition_number);

                // 对账单扫描件
                if(isset($payInfo['compact_url_name']) and $payInfo['compact_url_name']){
                    if(stripos($payInfo['compact_url_name'],',') !== false){
                        $compact_url_list = explode(',',$payInfo['compact_url']);
                        $compact_url_name_list = explode(',',$payInfo['compact_url_name']);
                        foreach($compact_url_name_list as $key => $compact_url_name){
                            $result[] = [
                                'file_name' => $compact_url_name,
                                'file_path' => isset($compact_url_list[$key])?$compact_url_list[$key]:'',
                            ];
                        }
                    }else{
                        $result[] = [
                            'file_name' => $payInfo['compact_url_name'],
                            'file_path' => $payInfo['compact_url'],
                        ];
                    }
                }
            }
        }
        if($result){
            $file= is_array($result)?array_column($result, 'file_path'):[];
            $file_name= is_array($result)?array_column($result, 'file_name'):[];
            $this->success_json(['file'=>$file,'file_name' => $file_name]);
        }else{
            $this->error_json('此文件不存在');
        }
    }

    /**
    * 打印合同(获取数据)
    * @author Jaxton 2019/01/30
    * /compact/compact/print_compact
    */
    public function print_compact(){
        $compact_number = $this->input->get_post('compact_number');
        if(empty($compact_number)){
            $this->error_json('缺少合同编号');
        }
        $result = $this->compact_model->get_print_compact_data($compact_number);
        if($result){
            $data = json_encode($result);
            if($result['compact_data']['is_drawback']==1){
                $key = "tax_compact";//缓存键
            }else{
                $key = "ntax_compact";//缓存键
            }
            $this->rediss->setData($key, $data);
            $this->success_json();
        }else{
            $this->error_json('未获取到数据');
        }
    }

    /**
    * 打印合同(获取模板)
    * @author Jaxton 2019/02/25
    * /compact/compact/print_compact_tmp
    */
    public function print_compact_tmp(){
        $compact_number=$this->input->get_post('compact_number');
        if(empty($compact_number)){
            $this->error_json('缺少合同编号');
        }
        $this->load->helper('status_order');
        try{
            $result = $this->compact_model->get_print_compact_data($compact_number);
            if($result){
                $result['compact_data']['is_freight']    = getFreightPayment($result['compact_data']['is_freight']);// 运费支付方
                $result['compact_data']['total_price']   = format_price($result['compact_data']['total_price']);

                $data =json_encode($result);
                if($result['compact_data']['is_drawback']==1){
                    $key = "tax_compact";//缓存键
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'tax_print_compact');
                }else{
                    $key = "ntax_compact";//缓存键
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'no_tax_print_compact');
                }
                $print_compact .= '?compact_number='.$compact_number;

                $this->rediss->setData($key.'-'.$compact_number, $data);
                $html = getCurlData($print_compact,'','get');//file_get_contents($print_compact);
                $this->success_json(['html' => $html,'key' => $key]);
            }else{
                $this->error_json('未获取到数据');
            }
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 下载合同HTML
     * @author Jaxton 2019/02/26
     * /compact/compact/download_compact
     */
    public function download_compact_html(){
        $compact_number=$this->input->get_post('compact_number');
        $switch_show_image=$this->input->get_post('switch_show_image');// 是否显示图片（1.显示（默认），2.不显示）
        if(empty($compact_number)){
            $this->error_json('缺少合同编号');
        }
        $this->load->helper('status_order');
        try{
            $result = $this->compact_model->get_print_compact_data($compact_number);

            if($result){
                $result['compact_data']['is_freight']    = getFreightPayment($result['compact_data']['is_freight']);// 运费支付方
                $result['compact_data']['total_price']   = format_price($result['compact_data']['total_price']);

                $result['switch_show_image'] = !empty($switch_show_image)?$switch_show_image:'1';
                $data =json_encode($result);
                if($result['compact_data']['is_drawback']==1){
                    $key = "tax_compact_excel";//缓存键
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'tax_print_compact_excel');
                }else{
                    $key = "ntax_compact_excel";//缓存键
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'no_tax_print_compact_excel');
                }
                $print_compact.= '?compact_number='.$compact_number;
                $this->rediss->setData($key.'-'.$compact_number, $data);
                $html = getCurlData($print_compact,'','get');//file_get_contents($print_compact);

                $this->success_json(['html' => $html,'key' => $key]);
            }else{
                $this->error_json('未获取到数据');
            }
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }

    }


    /**
    * 下载合同
    * @author Jaxton 2019/02/26
    * /compact/compact/download_compact
    */
    public function download_compact(){
        $compact_number=$this->input->get_post('compact_number');
        if(empty($compact_number)){
            $this->error_json('缺少合同编号');
        }
        $this->load->helper('status_order');
        try{
            $result = $this->compact_model->get_print_compact_data($compact_number);
            if($result){
                $result['compact_data']['is_freight']    = getFreightPayment($result['compact_data']['is_freight']);// 运费支付方
                $result['compact_data']['total_price']   = format_price($result['compact_data']['total_price']);

                $data =json_encode($result);
                if($result['compact_data']['is_drawback']==1){
                    $key = "tax_compact";//缓存键
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'tax_print_compact');
                }else{
                    $key = "ntax_compact";//缓存键
                    $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'no_tax_print_compact');
                }
                $print_compact .= '?compact_number='.$compact_number;

                $this->rediss->setData($key.'-'.$compact_number, $data);
                $html = getCurlData($print_compact,'','get');//file_get_contents($print_compact);

                $this->success_json(['html' => $html,'key' => $key]);
            }else{
                $this->error_json('未获取到数据');
            }
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }

    }


    /**
     * 批量下载合同
     */
    public function batch_download_compact(){
        set_time_limit(0);
        $params = $this->get_search_params();
        file_put_contents(get_export_path('test_log').'log_20201127.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $total = $this->compact_model->get_compact_list($params, $offsets, $limit, $page,'sum');

        $this->load->model('system/Data_control_config_model');
        try {
            file_put_contents(get_export_path('test_log').'log_20201127.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
            $result = $this->Data_control_config_model->insertDownData($params, 'COMPACT_PDF_EXPORT', '批量下载合同PDF', getActiveUserName(), 'zip', $total);
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
    *pdf——test
    */
    public function pdf_test(){
        $url = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_compact');
        $html = file_get_contents($url);
        $this->load->model('print_pdf_model');
        $this->print_pdf_model->writePdf($html);
    }

    /**
     * 根据合同单号获取合同的付款申请书与合同扫描件
     */
    public function get_compact_detail_file(){
        //合同单号
        $compact_number = $this->input->get_post('compact_number');
        $download_type = $this->input->get_post('download_type');
        if(empty($compact_number)){
            $this->error_json('缺少合同编号');
        }
        $ids= explode(',', $compact_number);
        $this->load->model('compact/Compact_file_model');

        $result = $this->compact_model->get_compact_detail_file($ids,$download_type);
        $resultData =[];
        if(!empty($result)){
            foreach ($result as $value){
                $results = $this->compact_model->get_pay_requisition_file($value['pur_number'],$value['requisition_number']);
                $data= urlencode(json_encode($results,JSON_UNESCAPED_UNICODE));
                $print_data = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_payment_apply');
                $url = $print_data."?data=".$data;
//                $html = file_get_contents($url);
                //合同扫描件
                $data_compact = $this->Compact_file_model->see_compact_file($value['pop_id'],$value['pc_id']);
                $data_compact_img = array();
                if(!empty($data_compact)) {
                    foreach ($data_compact as $val) {
                        $data_compact_img[] = $val['file_path'];
                    }
                }
                $resultData[] =array(
                    'requisition_list' => $url,
                    'compact_list'=> $data_compact_img,
                    'compact_number'=> $value['pur_number'],
                    'requisition_number'=> $value['requisition_number']
                );
            }
            if(empty($resultData)){
                $this->error_json('未获取到数据');
            }
            $this->success_json($resultData);
        }else{
            $this->error_json('未获取到数据');
        }
    }

    /**
     * 一键生成进货单（支持勾选和查询条件）
     * @author Jolon
     */
    public function one_key_compact_create(){
        set_time_limit(0);
        $purchase_number_list    = $this->input->get_post('purchase_numbers');
        if($purchase_number_list){
            if(!is_array($purchase_number_list))
                $purchase_number_list = explode(',',$purchase_number_list);

        }else{
            // 读取缓存的查询SQL
            $new_get_list_querySql = $this->rediss->getData(md5(getActiveUserId().'-new_get_list'));
            if(empty($new_get_list_querySql)) $this->error_json("缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $new_get_list_querySql = base64_decode($new_get_list_querySql);

            // 截取第一个FROM 和 最后一个GROUP BY 之间的字符串
            $new_get_list_querySql = preg_replace("/(LIMIT)[\w\W]+(\))/",')',$new_get_list_querySql);
            // 在付款状态=未申请付款、驳回（经理驳回、供应链总监驳回、财务驳回、财务主管驳回、财务总监驳回、总经办驳回）这些付款状态下可以点击
            $new_get_list_querySql = "SELECT `ppo`.`purchase_number`
                 FROM pur_purchase_order AS ppo
                 LEFT JOIN pur_purchase_order_items AS poi ON poi.purchase_number=ppo.purchase_number
                 WHERE poi.id IN (".$new_get_list_querySql." ) 
                 AND ppo.is_generate=1
                 AND (SELECT COUNT(1) FROM pur_purchase_compact_items AS tmp_ii WHERE tmp_ii.purchase_number=ppo.purchase_number) <=0 
                 GROUP BY `ppo`.`purchase_number` LIMIT 520";

            $purchase_number_list  = $this->compact_model->purchase_db->query($new_get_list_querySql)->result_array();
            $purchase_number_list  = array_column($purchase_number_list,'purchase_number');

        }
        //判断是否为供应商门户关联启用供应商
//        $unvalid_supplier = $this->supplier_joint_model->isValidSupplier($purchase_number_list);
//        if(is_array($unvalid_supplier)){
//            $unvalid_supplier = implode(',',$unvalid_supplier);
//            $msg = sprintf('供应商%s在供应商门户系统是无效状态',$unvalid_supplier);
//            $this->error_json($msg);
//        }
        //判断是否在供应商门户有效合同单
        $supplier_items = $this->supplier_joint_model->is_valid_status($purchase_number_list);
        if($supplier_items === true){
        }else{
            if(!empty($supplier_items)){
                $supplier_item = implode(',', $supplier_items);
//                $msg = sprintf('采购单%s未确认交期,请确认后再点击', $supplier_item);
//                $this->error_json($msg);
            }
        }
        $purchase_number_list = array_unique($purchase_number_list);
        if(empty($purchase_number_list)) $this->error_json('没有获取到待【待生成进货单】的数据，请确认操作');
        if(count($purchase_number_list) > 500) $this->error_json('亲，您选择的数据太多了，我会崩溃滴，请小于500个PO，谢谢');

        $this->load->model('compact/Compact_model','compactModelNew');
        $result = $this->compactModelNew->one_key_compact_create($purchase_number_list);
        if($result['code']){
            $this->success_json($result['data'],null,$result['message']);
        }else{
            $this->error_json($result['message']);
        }

    }

    /**
     * 供应商门户-合同审核日志
     */
    public function get_web_info_log(){
        $compact_number = $this->input->get_post('compact_number');
        $result = $this->supplier_joint_model->getInfoLog($compact_number);
        $this->load->helper('status_order');
        foreach ($result as &$v){
            $v['compact_audit_status'] = getCompactIsFileUploaded($v['compact_audit_status']);
        }
        if(!empty($result)){
        $this->success_json($result,null,'操作成功');
        }else{
            $this->error_json('无记录');
        }
    }

    /**
     * compact_number
     * compact_status 供应商门户-合同审核状态
     * remark 采购审核备注
     */
    public function audit_compact_status (){
        //'compact_num' => $param['compact_number'],
        //            'compact_audit_status' => $param['audit_status'],
        //            'remark' => $param['remark'],
        $compact_items = $this->input->get_post('compact_items'); //合同单号
//        $purchase_number_list = $this->input->get_post('purchase_number'); //采购审核状态
//        $purchase_number_list = $this->input->get_post('purchase_number'); //采购审核备注
        $compact_arr = json_decode($compact_items,true);
        //推送门户系统合同审核状态
//        var_dump($compact_arr);exit;
        $return = $this->supplier_joint_model->updateCompactStatus($compact_arr);
        if($return){
            $this->success_json($return, null,'审核成功');
        }else{
            $this->error_json('审核失败');
        }
    }

    /**
     * 合同列表数据导出
     */
    public function get_compact_export(){
        set_time_limit(0);
        ini_set('memory_limit', '3000M');
        $params = $this->get_search_params();
        file_put_contents(get_export_path('test_log').'log_20201127.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;

        $total = $this->compact_model->get_compact_list($params, $offsets, $limit, $page, 'sum');

        $this->load->model('system/Data_control_config_model');
        $ext = 'csv';
        try {
            $result = $this->Data_control_config_model->insertDownData($params, 'COMPACT_LIST_EXPORT', '合同列表导出', getActiveUserName(), $ext, $total);
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
}