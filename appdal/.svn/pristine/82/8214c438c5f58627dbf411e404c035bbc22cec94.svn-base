<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/4
 * Time: 10:46
 */
class Purchase_financial_audit_list extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_invoice_model', 'm_invoice', false, 'purchase');
        $this->load->model('Purchase_invoice_list_model', 'm_invoice_list', false, 'purchase');
        $this->load->model('Purchase_financial_audit_model', 'm_financial_audit', false, 'purchase');
        $this->load->model('Purchase_order_tax_model','m_purchase_tax',false,'purchase');
        $this->load->model('declare_customs_model','m_declare_customs',false,'purchase');
        $this->load->model('product_model','m_product',false,'product');
        $this->load->model('Purchase_order_cancel_model','m_cancel',false,'purchase');
        $this->load->model('purchase_user_model','purchase_user_model',false,'user');
        $this->load->model('Message_model');

    }

    /**
     * 财务审核列表
     * /purchase/purchase_financial_audit_list/financial_audit_list
     * @author Manson
     */
    public function financial_audit_list()
    {
        $page_data=$this->format_page_data();
        $params = [
            'invoice_number' => $this->input->get_post('invoice_number'), // 发票清单号
            'purchase_user_id' => $this->input->get_post('purchase_user_id'), // 采购员
            'supplier_code' => $this->input->get_post('supplier_code'), //供应商名称
            'audit_status' => $this->input->get_post('audit_status'), //审核状态 1[待确认] 2[待采购开票] 3[待财务审核] 4[已审核] 5[财务驳回]
            'auditTime' => $this->input->get_post('auditTime'), //审核开始时间
            'invoice_code_left' => $this->input->get_post('invoice_code_left'), //发票代码（左）
            'invoice_code_right' => $this->input->get_post('invoice_code_right'), //发票号码（右）
            'purchase_number' => $this->input->get_post('purchase_number'), //采购单号
            'compact_number' => $this->input->get_post('compact_number'), //合同单号
            'demand_number' => $this->input->get_post('demand_number'), //备货单号
            'sku' => $this->input->get_post('sku'), //SKU
            'export_save' => 1,
            'is_gateway' => $this->input->get_post('is_gateway'),
            'is_invoice_image' => $this->input->get_post('is_invoice_image'),
            'purchase_type' => $this->input->get_post('purchase_type'), // 业务线：1国内，2海外
            'tovoid_status' => $this->input->get_post('tovoid_status') // 作废查询
        ];

        $params['limit'] = $page_data['limit'];
        $params['offset'] = $page_data['offset'];
        $params['page'] = $page_data['page'];
//        pr($params);exit;

        $result = $this->m_financial_audit->get_financial_audit_list($params);
        if (!empty($result['value'])){
            $this->format_list_data($result['value']);
            $role_name=get_user_role();//当前登录角色
            $data_role= getRolexiao();
            $result['value'] = ShieldingData($result['value'],['supplier_name','supplier_code'],$role_name,NULL);
        }
        //添加下拉
        //采购员
        $user_list = $this->purchase_user_model->get_list();
        $purchase_user_list = array_column($user_list, 'name','id');
        //是否作废 1表示未作废，2表示已作废，3表示作废驳回，4表示作废申请
        $drop_down_box = [
            'purchase_user_list' => $purchase_user_list,
            'invoice_number_audit_status' => invoice_number_status(),
            'is_gateway' => [1=>'是',2=>'否'],
            'is_invoice_image' => [1=>'是',2=>'否'],
            'purchase_type'     => getPurchaseType(), //[1 => '国内仓', 2 => '海外', 3 => 'FBA', 4 => 'PFB', 5 => '平台头程']
            'tovoid_status'  => [1=>'未作废',4=>'作废待审核',2=>'已作废',3=>'作废驳回']
        ];
        $result['drop_down_box'] = $drop_down_box;
        $this->success_json($result, $result['page_data']);

    }


    public function format_list_data(&$data_list)
    {
        //开票信息
        $invoice_number_list = array_unique(array_column($data_list,'invoice_number'));
        $invoice_map = $this->get_invoice_map($invoice_number_list);
        //SKU集合 出口海关编码,
        $skus = array_unique(array_column($data_list, 'sku'));
        $sku_map = $this->get_sku_map($skus);
        //报关信息
        $purchase_number_list = array_unique(array_column($data_list,'purchase_number'));
        $customs_clearance_map = $this->get_customs_clearance_map($purchase_number_list);

        foreach ($data_list as $key => &$item){
            $_tag = sprintf('%s%s%s',$item['invoice_number'],$item['purchase_number'],$item['sku']);
            $ps_tag = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
            $item['invoice_info'] = $invoice_map[$_tag]??[];//开票信息
            if (!empty($item['invoice_info'])){
                $total_invoiced_qty = array_sum(array_column($item['invoice_info'],'invoiced_qty'));//总的开票数量
            }

            $item['customs_code'] = $sku_map[$item['sku']]['customs_code']??'';//出口海关编码
            $item['export_cname'] = $sku_map[$item['sku']]['export_cname']??'';//开票品名
            $item['declare_unit'] = $sku_map[$item['sku']]['declare_unit']??'';//开票单位

            //报关信息
            $item['customs_number'] = $customs_clearance_map[$ps_tag]['customs_number']??[];
            $item['customs_name'] = $customs_clearance_map[$ps_tag]['customs_name']??'';//
            $item['customs_unit'] = $customs_clearance_map[$ps_tag]['customs_unit']??'';//报关单位
            $item['customs_quantity'] = $customs_clearance_map[$ps_tag]['customs_quantity']??0;//sum_报关数量
            $item['no_customs_quantity'] = $item['upselft_amount'] - $item['customs_quantity'];//未报关数量
            $item['customs_type'] = $customs_clearance_map[$ps_tag]['customs_type']??'';//报关型号
            //$item['total_amount'] = bcmul($item['unit_price']??0,$total_invoiced_qty??0,2);//总金额 含税单价*已开票数量

            $item['total_amount'] = $item['invoiced_amount'];
            $items['tovoid_user'] = isset($item['tovoid_user'])?$item['tovoid_user']:'';
            $items['tovoid_time'] = isset($item['tovoid_time'])?$item['tovoid_time']:'';
            $items['is_tovoid_ch'] =isset($item['is_tovoid_ch'])?$item['is_tovoid_ch']:'';
            if(isset($item['invoice_image'])){

                $item['invoice_image'] = str_replace("//mnt/yibai_cloud/purchase/webfront/",CG_SYSTEM_WEB_FRONT_IP,$item['invoice_image']);
            }
            if (isset($item['audit_status'])){//导出
                $item['audit_status'] = invoice_number_status($item['audit_status']);
            }
        }

    }

    /**
     * 导出
     */
    public function export_list()
    {
        try{
            ini_set('memory_limit','1024M');
            set_time_limit(0);
            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');
            $params['ids'] = $this->input->get_post('ids');
            if (!empty($params['ids'])){
                $result = $this->m_financial_audit->get_financial_audit_list($params);
                $total = $result['total_count']??0;
                $quick_sql = $result['quick_sql']??'';
            }else{
                $this->load->service('basic/SearchExportCacheService');
                $quick_sql = $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_FINANCIAL_AUDIT_LIST_SEARCH_EXPORT)->get();
                $total = substr($quick_sql, 0, 10);
                $quick_sql = substr($quick_sql, 10);

                if (empty($quick_sql))
                {
                    throw new \Exception(sprintf('请选择要导出的资源'));
                }
            }

            $file_name = sprintf('财务审核列表_%s.csv',time());//文件名称
            $product_file = get_export_path().$file_name;//文件下载路径
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file,'w');
            $fp = fopen($product_file, "a");
            $this->load->classes('purchase/classes/FinancialAuditTemplate');
            $pick_cols = $this->FinancialAuditTemplate->get_default_template_cols();
            $pick_cols['作废审核人'] = [

                'col' => 'void_audit_user',
                'width' => 18
            ];

            $pick_cols['作废审核备注'] = [
                'col' => 'tovoid_remark',
                'width' => 18
            ];

            $pick_cols['作废审核时间'] = [

                'col' => 'void_audit_time',
                'width' => 18
            ];
            $picksColsKey = array_keys($pick_cols);
            if(!in_array("开票单位",$picksColsKey)){

               $pick_cols['开票单位'] = [

                   'col' => 'declare_unit',
                   'width' =>18
               ];
            }
            foreach( $pick_cols as $key => $val) {

                $title[$val['col']] =iconv("UTF-8", "GBK//IGNORE",$key);
            }

            $pick_cols = array_column($pick_cols,'col');


            //将标题写到标准输出中
            fputcsv($fp, $title);
            if($total>=1) {
                $limit      = 1000;
                $total_page = ceil($total / $limit);
                $time_cols  = ['audit_time', 'submit_time'];
                $tab_cols = ['invoice_code_left', 'invoice_code_right','customs_number','customs_code'];
                $special_cols = ['product_name'];
                for ($i = 1; $i <= $total_page; ++$i) {

                    $offset    = ($i - 1) * $limit;
                    $sql = sprintf('%s LIMIT %s, %s', $quick_sql, $offset, $limit);
                    $result    = $this->m_financial_audit->query_quick_sql($sql);
                    foreach($result as $key=>$value){

                        if( $value['is_tovoid'] == 1){

                            $result[$key]['is_tovoid_ch'] = '未作废';
                        }else if($value['is_tovoid'] == 2){

                            $result[$key]['is_tovoid_ch'] = '已作废';
                        }else if($value['is_tovoid'] == 3){

                            $result[$key]['is_tovoid_ch'] = '作废驳回';
                        }else if($value['is_tovoid'] == 4){
                            $result[$key]['is_tovoid_ch'] = '作废申请';
                        }
                    }
                    $this->format_list_data($result);
                    foreach ($result as $row) {
                        $new = [];
                        foreach ($pick_cols as $col) {
                            if(in_array($col, $special_cols)){
                                $row[$col] = str_replace(array("\r\n", "\r", "\n"), '', $row[$col]);//将换行
                                $row[$col] = str_replace(',',"，",$row[$col]);//将英文逗号转成中文逗号
                                $row[$col] = str_replace('"',"”",$row[$col]);//将英文引号转成中文引号
                            }
                            if (in_array($col, $time_cols)) {
                                $new[$col] = empty($row[$col]) || $row[$col] == '0000-00-00 00:00:00' ? '' : $row[$col] . "\t";
                            }elseif ($col == 'customs_number' && isset($row['customs_number'])) {
                                $new[$col] = implode(' ', $row['customs_number']);
                            }elseif (in_array($col, $tab_cols)){
                                $new[$col] = $row[$col] . "\t";
                            } elseif (isset($row[$col])) {

                                $new[$col] = $row[$col];
                            }


                            if (!empty($new[$col])) {
                                $new[$col] = iconv("UTF-8", "GBK//IGNORE", $new[$col]);
                            } else {
                                $new[$col] = '';
                            }
                        }
                        fputcsv($fp, $new);
                    }
                    //刷新缓冲区
                    ob_flush();
                    flush();
                }
            }
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$file_name;
            $this->success_json($down_file_url);
        }catch (Exception $e){
            $this->error_json($e->getMessage());
        }

    }


    /**
     * 产品表相关信息
     * @author Manson
     */
    public function get_sku_map($skus)
    {
        $product_field = 'sku,customs_code,declare_cname,declare_unit,export_cname,product_name';
        $sku_map = $this->m_product->get_list_by_sku($skus,$product_field);
        return $sku_map;
    }

    /**
     * 开票信息
     */
    public function get_invoice_map($invoice_number_list)
    {
        $invoice_map = [];
        $field = 'a.id as invoice_detail_id, a.invoice_number, a.purchase_number, a.sku,  a.invoiced_qty';
        $invoice_detail = $this->m_invoice_list->get_invoice_detail($invoice_number_list,$field);
        foreach ($invoice_detail as $key => $item){
            $_tag = sprintf('%s%s%s',$item['invoice_number'],$item['purchase_number'],$item['sku']);
            $invoice_map[$_tag][] = $item;
        }
        unset($invoice_number_list);
        unset($invoice_detail);
        return $invoice_map;
    }

    /**
     * 报关信息
     */
    public function get_customs_clearance_map($purchase_number_list)
    {
        $customs_clearance_map = $this->m_declare_customs->get_customs_clearance_details($purchase_number_list);
        return $customs_clearance_map;
    }

    /**
     * 审核时的详情页
     * /purchase/purchase_financial_audit_list/audit_invoice_detail
     * @author Manson
     */
    public function audit_invoice_detail()
    {
        $invoice_detail_id = $this->input->get_post('invoice_detail_id');
        if (empty($invoice_detail_id)){
            $this->error_json('id不能为空');
        }
        $result = $this->m_financial_audit->get_audit_invoice_detail($invoice_detail_id);
        if (!empty($result['value'])){
            $result = $this->organize_data($result);
            $this->success_json($result);
        }else{
            $this->error_json('未查询到待财务审核数据');
        }
    }

    /**
     * 批量审核-弹出的详情页(19027需求改动后 现在查询的方法是调的同一个)
     * /purchase/purchase_financial_audit_list/batch_audit_invoice_detail
     * @author Manson
     */
    public function batch_audit_invoice_detail()
    {
        //勾选时已po+sku维度查询
        $ids = $this->input->get_post('ids');
        $ids = explode(',',$ids);
        if (empty($ids)){
            $this->error_json('id不能为空');
        }
        $result = $this->m_financial_audit->get_audit_invoice_detail($ids);
        if (!empty($result['value'])){
            $result = $this->organize_data($result);
            $this->success_json($result);
        }else{
            $this->error_json('未查询到待财务审核数据');
        }
    }


    /**
     * 组织数据
     * @author Manson
     * @param $result
     * @return mixed
     */
    public function organize_data($result)
    {
        //SKU集合 出口海关编码,
        $skus = array_unique(array_column($result['value'], 'sku'));
        $sku_map = $this->get_sku_map($skus);
        //报关信息
        $purchase_number_list = array_unique(array_column($result['value'],'purchase_number'));
        $customs_clearance_map = $this->get_customs_clearance_map($purchase_number_list);
        $data_list = [];
        foreach ($result['value'] as $key => $item){
            $ps_tag = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
            $item['customs_code'] = $sku_map[$item['sku']]['customs_code']??'';//出口海关编码
            $item['product_name'] = $sku_map[$item['sku']]['product_name']??'';//产品名称
            $item['export_cname'] = $sku_map[$item['sku']]['export_cname']??'';//开票品名

            //报关信息
            $item['customs_number'] = $customs_clearance_map[$ps_tag]['customs_number']??[];//报关单号
            $item['customs_name'] = $customs_clearance_map[$ps_tag]['customs_name']??'';//报关品名
            $item['customs_unit'] = $customs_clearance_map[$ps_tag]['customs_unit']??'';//报关单位
            $item['customs_quantity'] = $customs_clearance_map[$ps_tag]['customs_quantity']??0;//sum_报关数量
            $item['customs_type'] = $customs_clearance_map[$ps_tag]['customs_type']??'';//报关型号
            $data_list[$item['invoice_number']][] = $item;
//
//            $item['invoiced_amount'] = bcmul($item['invoiced_qty'],$item['unit_price']);//已开票金额 [已开票金额 = 已开票数量*含税单价]
        }
        $result['value'] = $data_list;
        return $result;
    }

    /**
     * 根据发票代码的维度进行审核
     * 支持审核通过 审核驳回 批量审核
     * /purchase/purchase_financial_audit_list/batch_audit
     * @author Manson
     */
    public function batch_audit(){

        try{
            $ids_str = $this->input->get_post('ids');//invoice_info表的id
            $remark = $this->input->get_post('remark');//备注
            $audit_status = $this->input->get_post('audit_status');//4审核通过 5审核驳回
            if (empty($ids_str)){
                throw new Exception('参数ids不能为空');
            }
            if (!isset($audit_status) || !in_array($audit_status,[INVOICE_AUDITED,INVOICE_FINANCIAL_REJECTION])){
                throw new Exception('审核参数错误');
            }
            $ids = explode(',',$ids_str);


            $result = $this->m_financial_audit->get_audit_status($ids);
            if (empty($result)){
                throw new Exception('未查询到对应的数据');
            }
            $compact_number = array_unique(array_column($result,'compact_number'));

            //审核状态
            $audit_status_map = array_column($result,'audit_status','id');
            //开票数量
            $invoiced_qty_map = [];
//            pr($result);exit;
            foreach ($result as $item){
                if (isset($invoiced_qty_map[$item['items_id']] )){
                    $invoiced_qty_map[$item['items_id']] += $item['invoiced_qty'];
                }else{
                    $invoiced_qty_map[$item['items_id']] = $item['invoiced_qty'];
                }
            }

            $items_id = array_unique(array_column($result,'items_id'));
            $_ids = array_keys($audit_status_map);
            $diff = array_diff($ids,$_ids);//表中不存在的数据
            if (!empty($diff)){
                throw new Exception(sprintf('数据异常,记录已经不存在:%s',implode(',',$diff)));
            }

            foreach ($ids as $id){
                $_audit_status = $audit_status_map[$id]??'';
                if ($_audit_status != INVOICE_STATES_WAITING_FINANCE_AUDIT){
                    throw new Exception(sprintf('操作失败,状态不是待财务审核状态'));
                }
            }

            $update_invoice_status = [];
            if ($audit_status == INVOICE_FINANCIAL_REJECTION){ //审核驳回
                $update_data = [
                    'ids' => $ids,
                    'remark' => $remark,
                    'audit_status' => $audit_status,
                    'audit_time' => date('Y-m-d H:i:s'),
                    'audit_user' => getActiveUserName(),
                ];
                $_db = $this->m_financial_audit->getDatabase();
                $_db->trans_start();
                //1.更新审核状态
                $this->m_financial_audit->update_audit_status($ids_str,$remark,$update_data);

                //2.审核失败,更新开票状态
                $this->m_financial_audit->update_invoice_status($items_id,[]);

                $_db->trans_complete();
                if ($_db->trans_status() == false){
                    throw new Exception('审核失败,请稍后再试');
                }
                $idsStrData = explode(",",$ids_str);
                $this->load->model('Message_model');
                $this->Message_model->AcceptMessage('declare',['data'=>$idsStrData,'message'=>$remark,'user'=>getActiveUserName(),'type'=>'财务审核']);

            }elseif ($audit_status == INVOICE_AUDITED){ //审核通过
                //更新审核状态
                $update_data = [
                    'ids' => $ids,
                    'remark' => $remark,
                    'audit_status' => $audit_status,
                    'audit_time' => date('Y-m-d H:i:s'),
                    'audit_user' => getActiveUserName(),
                ];
                $_db = $this->m_financial_audit->getDatabase();
                $_db->trans_start();
                //1.更新审核状态
                $this->m_financial_audit->update_audit_status($ids_str,$remark,$update_data,$update_invoice_status);

                //2.审核成功,更新开票状态
                $this->m_financial_audit->update_invoice_status($items_id,$invoiced_qty_map);

                //3.合同开票状态
                $this->m_financial_audit->update_contract_invoicing_status($compact_number);

                $_db->trans_complete();
                if ($_db->trans_status() == false){
                    throw new Exception('审核失败,请稍后再试');
                }

            }else{
                throw new Exception('参数错误');
            }

            //财务审核成功,记录日志
            $logsData = [];
            foreach($ids as $logids) {


            $examine = '';

                if ($audit_status == INVOICE_AUDITED) {
                    $examine = "财务审核通过";
                }
                if ($audit_status == INVOICE_FINANCIAL_REJECTION) {
                    $examine = "财务审核驳回";
                }
                $logsData[] = [
                    'examine_user' => getActiveUserName(),
                    'examine_time' => date('Y-m-d H:i:s'),
                    'remark' => $remark,
                    'ids' => $logids,
                    'examine' =>$examine
                ];
            }
            $this->m_financial_audit->insertLogsData($logsData);


            // 审核结果推送到门户系统
            if(!empty($ids)){

                $infoDatas = $this->m_financial_audit->get_invoice_info($ids);
                $url = getConfigItemByName('api_config', 'invoice', 'reject');
                $header = array('Content-Type: application/json');
                $access_taken = getOASystemAccessToken();
                $url = $url . "?access_token=" . $access_taken;
                foreach($infoDatas as $info_key=>$info_value) {
                    $pushData = [

                        'invoiceNumber' => $info_value['invoice_number'],
                        'purchaseNumber' => $info_value['purchase_number'],
                        'sku' => $info_value['sku'],
                        'invoiceCodeLeft' => $info_value['invoice_code_left'],
                        'invoiceCodeRight' => $info_value['invoice_code_right'],
                        'auditType' => ($audit_status == INVOICE_AUDITED)?1:2,
                        'auditUser' => getActiveUserName(),
                        'rejectRemark' => $remark,
                        'invoiceImage' => $info_value['invoice_image'],
                        'invoiceNumberSub' => $info_value['children_invoice_number']

                    ];
                    $result = getCurlData($url, json_encode($pushData, JSON_UNESCAPED_UNICODE), 'post', $header);
                    $this->m_financial_audit->insertData(json_encode($pushData, JSON_UNESCAPED_UNICODE),$result,"batch_audit");
                }
            }

            $this->success_json('操作成功');
        }catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 采购确认
     * @author:luxu
     * @time:2020/5/6
     **/
    public function updatePurchaseConfirm(){

        try{

           $httpData  = $this->input->get_post('data'); // 确认数据
           $remarkMessage = $this->input->get_post('remark'); // 备注信息
           $status   = $this->input->get_post('status'); // 审核状态  1表示通过（提交）/ 2表示驳回\
           if( empty($httpData) || empty($remarkMessage) || empty($status) ){

               throw new Exception("请传入相关参数");
           }

           if( $status == 2 && empty($remarkMessage)){

               throw new Exception("驳回请填写备注信息");
           }
           
           $data = json_decode($httpData,True);
           if(empty($data)){
               throw new Exception("数据格式解析错误");
           }
           
           foreach($data as $key=>$value){
               
               if( isset($value['status'])
                   && !empty($value['status'])
                   && $value['status'] == 2
                   && empty($value['remark'])){

                   throw new Exception("子发票清单号：".$value['financialnumber']."驳回，请填写备注");
               }


           }
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废接口
     * @author:luxu
     * @time:2020/8/4
     **/

    public function toVoid(){

        try{

            // 接受HTTP 传入的子发票清单号
            $childrenNumbers = $this->input->get_post('childrenNumbers');
            // 接受HTTP 传入的 申请作废的备注信息
            $remark = $this->input->get_post('remark');
            // 判断HTTP 传入的子发票清单是否为空或者是否为数组
            if( empty($childrenNumbers) || !is_array($childrenNumbers)){

                throw new Exception("请传入子发票清单号，或 检查子发票清单号是否以数组的形式传入");
            }

            $result = $this->m_financial_audit->toVoid($childrenNumbers,$remark);
            if($result){

                $this->success_json('申请成功');
            }else{
                throw new Exception("申请失败");
            }

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废申请接口
     * @author:luxu
     * @time:2020/8/4
     **/
    public function ToVoidAudit(){

        try{


            // 接受HTTP 传入的子发票清单号
            $childrenNumbers = $this->input->get_post('childrenNumbers');
            // 接受HTTP 传入的 申请作废的备注信息
            $remark = $this->input->get_post('remark');
            // 接受HTTP 传入的审核结果 2表示审核通过，3表示审核驳回
            $auditStatus = $this->input->get_post('auditStatus');
            // 判断HTTP 传入的子发票清单是否为空或者是否为数组
            if( empty($childrenNumbers) || !is_array($childrenNumbers)){

                throw new Exception("请传入子发票清单号，或 检查子发票清单号是否以数组的形式传入");
            }


            // 判断审核结果是否传入

            if( empty($auditStatus)){

                throw new Exception("请传入正确的审核结果");
            }

            $result = $this->m_financial_audit->ToVoidAudit($childrenNumbers,$auditStatus,$remark);

            if($auditStatus ==2) {
                $url = getConfigItemByName('api_config', 'CoPurRejectOrder_api', 'CoPurRejectOrder');
                //childrenNumbers
                $pushData = [];
                foreach($childrenNumbers as $numbers){

                    $pushData[] = ['inventory_number' => $numbers];
                }
               // $url.="?batch_data=".json_encode($pushData);
                $result =   getCurlData($url,['batch_data'=>json_encode($pushData)]);
                $api_log = [
                    'record_number' => 'pushSysInsertData',
                    'api_url' => $url,
                    'record_type' => '采购系统推送财务系统',
                    'post_content' => json_encode($pushData),
                    'response_content' => $result,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                apiRequestLogInsert($api_log);

            }
            $this->success_json('作废成功');
        }catch ( Exception $exception) {

            $this->error_json($exception->getMessage());
        }
    }

    /**
     * 开票状态=未开票的，用户可以点击【无法开票】，否则报错：备货单号**只有开票状态=未开票才可点击
     * @author:luxu
     * @time:2020/8/11
     **/

    public function unableToInvoice(){

        try{

            $data = $this->input->get_post('datas');
            $data = json_decode($data,True);
            if(empty($data) || !is_array($data))
            {
                throw new Exception("请传入采购单号SKU信息");
            }

            $result = $this->m_financial_audit->unableToInvoice($data);
            if(True == $result){

                $this->success_json('操作成功');
            }

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }


/*    public function export_list()
    {
        try
        {
            $post = $this->input->post();
            $this->load->service('purchase/FinancialAuditExportService');
            $this->financialauditexportservice->setTemplate($post);
            $this->data['filepath'] = $this->financialauditexportservice->export('csv');
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }*/
}