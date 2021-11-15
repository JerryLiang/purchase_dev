<?php
/**
 * 拉卡拉支付（支付平台拓展类）
 *      最开始该类只用作拉卡拉支付业务接口，由于新增加支付平台由该类进行拓展，避免重复增加冗余非常高的代码
 * Created by PhpStorm.
 * User: totoro Jolon
 * Date: 2020-06-02
 * Time: 16:58
 */



class Lakala_pay extends MY_Controller {


    public function __construct(){
        parent::__construct();
        $this->load->model("Lakala_pay_model","lakala_pay");
        $this->load->helper('status_order');
    }

    /**
     * 应付单 拉卡拉支付
     */
    public function lakala_submission(){
        //POST提交情况下
        if(IS_POST){
            $post                       = gp();
            $platform_type              = strtolower($post['type']);
            $account_short              = $post['account_short'];

            $ids = explode(',', $post['ids']);
            if(!empty($ids) && !is_array($ids)){
                $this->error_json('参数ids格式不正确');
            }

            // Start：严格验证数据，避免重复提交
            sort($ids);
            $session_key = 'online_payment'.md5(implode(',',array_unique($ids)));
            $session_type = $this->rediss->getData($session_key);
            if(empty($session_type)){
                $this->error_json('请求类型已过期，请重新打开支付申请页面');
            }
            if($session_type != $platform_type){
                $this->error_json('支付请求提交类型与预览请求类型不一致');
            }
            // End：严格验证数据，避免重复提交

            $PayDatas                  = [];
            $PayDatas['ids']           = $post['ids'];
            $PayDatas['charge']        = $post['charge'] == 0 ? "00" : '01';      //是否手续费
            $PayDatas['to_acc_name']   = $post['account_name']; //收款方名称
            $PayDatas['trans_card_id'] = $post['id_number']; //收款方证件号
            $PayDatas['to_bank_name_main']  = $post['payment_platform_bank']; //主行号(或主行名称)
            $PayDatas['to_bank_name']  = $post['payment_platform_branch']; //支行号(或支行名称)
            $PayDatas['to_acc_no']     = $post['account']; //收款账号
            $PayDatas['trans_money']   = $post['total_pay_price']; //转账金额
            $PayDatas['trans_mobile']  = $post['phone_number']; //收款方手机号
            $PayDatas['remark']        = $post['remark']; //付款备注
            $PayDatas['bank_code']     = isset($post['bank_code'])?$post['bank_code']:'';//开户行code
            $PayDatas['platform_type'] = $post['type']; // 支付平台类型
            if($post['charge']==1){// 我方承担手续费
                $procedure_party = PAY_PROCEDURE_PARTY_A;// 甲方
                $procedure_fee   = 0;
            }else{// 非我方承担手续费
                $procedure_party = PAY_PROCEDURE_PARTY_B;// 乙方
                $procedure_fee   = 0;
            }
            $reslut                    = $this->lakala_pay->pay_lakala($PayDatas,$procedure_party,$procedure_fee,$account_short);
            if($reslut['code']){
                $this->success_json([], null, $reslut['msg']);
            }else{
                $this->error_json($reslut['msg']);
            }
        }else{
            $this->error_json('请求方式为POST');
        }
    }

    /**
     * 界面列表
     */
    public function LaKaLa_list(){
        $get   = gp();
        $page  = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if(empty($page) or $page < 0) $page = 1;
        $limit   = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->lakala_pay->LaKaLa_list($get, $offsets, $limit, $page);
        $this->success_json($data_list);
    }

    /**
     * 审核时显示的列表
     */
    public function audit_list(){
        $ids = $this->input->get_post('ids');//请求参数
        if(empty($ids)){
            $this->error_json('参数id 不能为空');
        }
        $ids = explode(',', $ids);
        if(count($ids) > 20){
            $this->error_json('批量支付不能超过20个');
        }
        //判断付款状态
        //过滤掉不满足审核条件的数据
        $res = $this->lakala_pay->lakala_status($ids);
        if(!$res['code']){
            $this->error_json($res['msg']);
        }
        //返回数据
        $reslut = $this->lakala_pay->pay_Audit_list($ids);
        $this->success_json($reslut);
    }

    /**
     * 拉卡拉驳回接口
     */
    public function lakala_reject(){
        if(IS_POST){
            $post          = gp();
            $ids           = isset($post['ids']) ? $post['ids'] : '';
            $trans_summary = isset($post['remark']) ? $post['remark'] : '';
            if(empty($ids)){
                $this->error_json('参数id，不能为空');
            }
            if(empty($trans_summary)){
                $this->error_json('驳回请填写备注');
            }
            $ids    = explode(',', $ids);
            $reslut = $this->lakala_pay->pay_lakala_reject($ids, $trans_summary);
            if($reslut['code']){
                $this->success_json([], null, $reslut['message']);
            }else{
                $this->error_json($reslut['message']);
            }
        }else{
            $this->error_json('请求方式为POST');
        }
    }

    /**
     * 操作审核后将数据注册到拉卡拉
     * Totoro Jolon
     * 2020-06-16
     * 拉卡拉:批量代付注册接口
     * 易佰：拉卡拉界面审核
     * batchPay/registry
     */
    public function batchPay_registry(){
        set_time_limit(0);

        // 验证请求数据
        $id   = $this->input->get_post('ids');
        $note = $this->input->get_post('remark');//审核或者驳回备注
        if(empty($id)){
            $this->error_json('请勾选要操作的数据!');
        }
        if (empty($note)){
            $this->error_json('备注不允许为空!');
        }
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        //过滤掉不满足审核条件的数据
        $res = $this->lakala_pay->lakala_status($id);
        if(!$res['code']){
            $this->error_json($res['msg']);
        }

        //获取付款的数据
        $data_list = $this->lakala_pay->get_pay_info($id);

        $success_list = $error_list = [];
        //请求的数据计入日志
        if(!empty($data_list)) {
            $this->load->model('finance/Bank_info_model');

            foreach($data_list as $value){
                $cust_order_no = $value['cust_order_no'];
                $platform_type = $value['platform_type'];

                $pur_number_var = $this->lakala_pay->get_lakala_pur_number([$cust_order_no]);
                $pur_number_var = isset($pur_number_var[$cust_order_no])?$pur_number_var[$cust_order_no]:'';
                $response_status = [
                    'cust_order_no' => $cust_order_no,
                    'audit_status'  => null,
                    'data'          => null,
                    'message'       => null,
                    'data_status'   => null,
                ];

                // 按支付平台进行 对应操作，加载 类库
                if($platform_type == 'lakala'){
                    if(!isset($lakalaApi)){
                        $this->load->library('Lakala');
                        $lakalaApi = new Lakala();
                    }
                    $result = $lakalaApi->batchPay_registry([ 0 => $value ]);// 每次请求一个拉卡拉支付单
                    if(isset($result['encData']) && $result['encData']){
                        $response_status['audit_status'] = 2;
                        $response_status['data']         = $result['encData']['fileBatchNo'];
                        $response_status['data_status']  = 7;
                        $response_status['message']      = $result['message'];
                    }else{
                        $response_status['audit_status'] = 3;
                        $response_status['message']      = isset($result['message'])?$result['message']:'拉卡拉受理失败';
                    }

                }
                elseif($platform_type == 'cebbank'){
                    if(!isset($cebBankApi)){
                        $this->load->library('CebBank');
                        $cebBankApi = new CebBank();
                    }

                    $bankInfo = $this->Bank_info_model->get_one_bank_info_by_branch_bank_name($value['bank_name']);
                    if(empty($bankInfo) or empty($bankInfo['bank_union_code'])){
                        $error_list[] = $pur_number_var.':银行联行号资料缺失，请确认支行是否出错，若核对无误则联系IT维护资料';
                        continue;
                    }

                    $value['transferType'] = (trim($value['bank_name_main']) == '中国光大银行')? '2122':'2120';
                    $value['perOrEnt']     = $value['pay_type'] == 2 ? '0':'1'; // 0.对公，1.对私
                    $value['cnaps_code']   = $bankInfo['bank_union_code'];// 联行号

                    $result = $cebBankApi->single_order_b2e004001($value);// 获取转发的XML报文内容
                    if(empty($result['code'])){
                        $error_list[] = $pur_number_var.':'.$result['errorMsg'];
                        continue;
                    }

                    $response = $cebBankApi->curlPostXml($result['data']['url'],$result['data']['RequestXmlStr']);
                    if(isset($response['TransContent']['ReturnCode']) and $response['TransContent']['ReturnCode'] === '0000'){
                        $ClientPatchID = $response['TransContent']['RespData']['ClientPatchID'];
                        $RespData_respond2 = $response['TransContent']['RespData']['respond2'];
                        $response_status['audit_status'] = 2;
                        $response_status['data']         = $RespData_respond2;// 付款回单-付款流水号
                        $response_status['order_number_2'] = '';
                        $response_status['data_status']  = 7;
                    }elseif(isset($response['ReturnCode'])){
                        $response_status['audit_status'] = 3;
                        $response_status['message']      = isset($response['ReturnMsg'])?$response['ReturnMsg']:(isset($response['error'])?$response['error']:'光大本次交易失败');
                    }else{
                        $response_status['audit_status'] = 3;
                        $response_status['message']      = '本次交易失败[光大响应数据解析失败]';
                    }
                }

                if($response_status['audit_status'] == 2){// audit_status=2审核通过，更新单据状态
                    //记录成功日志
                    $data = [
                        'audit_status'  => 2,
                        "file_batch_no" => $response_status['data'],
                        "order_number_2"=> isset($response_status['order_number_2'])?$response_status['order_number_2']:'',// 文件编号-付款流水号
                        'submit_date'   => date('Ymd'),
                        'note'          => $note,
                        'lakala_state'  => $response_status['data_status'],// 参考 Lakala::$lakala_state 属性
                        'drawee_time'   => date('Y-m-d H:i:s')
                    ];
                    $fse  = $this->lakala_pay->update_pay_audit_status($cust_order_no, $data);
                    if(isset($fse['code']) and $fse['code']){
                        $success_list[] = $pur_number_var.':支付平台受理成功';
                    }else{
                        $error_list[] = $pur_number_var.':支付平台受理成功: '.$fse['message'];
                    }

                }elseif($response_status['audit_status'] == 3){// audit_status=3审核不通过，更新单据状态
                    $data = [
                        'audit_status' => 3,
                        'submit_date'  => date('Ymd'),
                        'note'         => $note,
                        'drawee_time'  => date('Y-m-d H:i:s')
                    ];
                    $fse  = $this->lakala_pay->update_pay_audit_status($cust_order_no, $data);//记录失败日志
                    if(isset($fse['code']) and $fse['code']){
                        $error_list[] = $pur_number_var.':支付平台受理失败: '.$response_status['message'];
                    }else{
                        $error_list[] = $pur_number_var.':支付平台受理失败: '.$response_status['message'].'=>'.$fse['message'];
                    }
                }
            }

            $this->success_json(['success_list' => $success_list,'error_list' => $error_list]);
        }else{
            $this->error_json('获取数据失败');
        }
    }

    /**
     * 拉卡拉导出
     */
    public function lakala_export(){
        $get           = gp();
        $data_list     = $this->lakala_pay->LaKaLa_export_sum($get,1,1);
        $total         = $data_list['paging_data']['total'];
        unset($data_list);

        // 生成下载的文件
        $template_file = 'lakala_pay_'.date('YmdHis').mt_rand(1000, 9999).'.csv';
        if($total > 100000){//一次最多导出10W条
            $template_file = 'lakala_pay.csv';
            $down_host     = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = get_export_path_replace_host(get_export_path('lakala_pay'),$down_host).$template_file;
            $this->success_json($down_file_url);
        }
        $product_file  = get_export_path('lakala_pay').$template_file;
        if(file_exists($product_file)){
            unlink($product_file);
        }
        fopen($product_file, 'w');
        $is_head = false;
        $fp      = fopen($product_file, "a");
        if($total > 0){
            $per_page   = 20;
            $total_page = ceil($total / $per_page);
            for($i = 1; $i <= $total_page; $i++){
                $offset = ($i - 1) * $per_page;
                $data   = $this->lakala_pay->LaKaLa_export($get, $offset, $per_page,$i);
                if(!empty($data['values'])){
                    //组装需求数据格式
                    foreach($data['values'] as $key => $value){
                        $row = [
                            $value['audit_status'],
                            $value['pur_number'],
                            $value['supplier_name'],
                            $value['amount'],
                            $value['acc_name']."\t",
                            $value['bank_name'],
                            $value['acc_no'],
                            $value['file_batch_no'],
                            $value['phone_no'],
                            $value['drawee'],
                            $value['drawee_time'],
                            $value['applicant'],
                            $value['applicant_time'],
                            "申请备注：".$value['remark']."\t\n"."审核备注:".$value['note'],
                        ];

                        foreach($row as $vvv){
                            if(preg_match("/[\x7f-\xff]/", $vvv)){
                                $vvv = stripslashes(iconv('UTF-8', 'GBK//IGNORE', $vvv));//中文转码
                            }
                            if(is_numeric($vvv) && strlen($vvv) > 9){
                                $vvv = $vvv."\t";//避免大数字在csv里以科学计数法显示
                            }
                            $row_list[] = $vvv;
                        }

                        if($is_head === false){
                            $heads = [
                                '审核状态',
                                '合同号',
                                '供应商名称',
                                '转账金额',
                                '收款名称',
                                '开户行名称',
                                '收款账号',
                                '收款人身份证',
                                '收款人手机号',
                                '审核人',
                                '审核时间',
                                '提交人',
                                '提交时间',
                                '备注'
                            ];
                            foreach($heads as &$m){
                                $m = iconv('UTF-8', 'GBK//IGNORE', $m);
                            }
                            fputcsv($fp, $heads);
                            $is_head = true;
                        }

                        fputcsv($fp, $row_list);
                        unset($row_list);
                        unset($row);
                    }
                    ob_flush();
                    flush();
                    usleep(100);
                }
            }
        }
        $down_host     = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url = get_export_path_replace_host(get_export_path('lakala_pay'),$down_host).$template_file;
        $this->success_json($down_file_url);
    }


    /**
     * 批量代付解析回盘文件下载接口
     */
    public function downloadErrorBackFile(){
        $file_batch_no = $this->input->get_post('file_batch_no');//批次
        $submit_date = $this->input->get_post('submit_date');//提交时间
        if(empty($file_batch_no) or empty($submit_date)){
            $this->error_json('拉卡拉批次号与提交时间必填');
        }

        $this->load->library('Lakala');
        $lakala = new Lakala();
        $result = $lakala->downloadErrorBackFile($file_batch_no,$submit_date);

        if(isset($result['downloadFile'])){
            $downloadFile = $result['downloadFile'];
        }else{
            $downloadFile = isset($result['message'])?$result['message']:'查询无结果!';
        }
        $this->success_json($downloadFile);
    }

    /**
     * 刷新交易状态
     */
    public function refresh_pay_status(){
        $pur_tran_num_list = $this->input->get_post('pur_tran_num_list');
        if(empty($pur_tran_num_list)){
            $this->error_json('参数 pur_tran_num_list，不能为空');
        }
        if(!is_array($pur_tran_num_list)){
            $this->error_json('参数 pur_tran_num_list，必须为数组');
        }

        $success_list = $error_list = [];
        foreach($pur_tran_num_list as $pur_tran_num){
            $result_flag = $this->lakala_pay->refresh_pay_status($pur_tran_num);

            if($result_flag === true){
                $success_list[] = $pur_tran_num.'：查询成功';
            }else{
                $error_list[] = $pur_tran_num.'：'.$result_flag;
            }
        }

        $this->success_json(['success_list' => $success_list,'error_list' => $error_list]);
    }
}