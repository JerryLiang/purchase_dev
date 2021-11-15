<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
use PHPUnit\Framework\TestCase;


class Export_server_handle extends MY_API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_new_model');
        $this->load->model('purchase/purchase_order_progress_model');
        $this->load->model('system/data_center_model');
        $this->load->model('product/product_model');
        $this->load->model('product/product_line_model', 'product_line', false, 'product');
        $this->load->model('statement/Purchase_inventory_items_model');
        $this->load->library('Export');
        $this->load->model('statement/Charge_against_surplus_model');
        $this->load->model('statement/Supplier_balance_model','balance');
        $this->load->model('purchase/Reduced_edition_model');

        $this->load->model('user/User_group_model');

        $this->load->model('purchase/delivery_model','delivery');
        $this->load->helper('status_product');

        $this->load->model('purchase/purchase_order_determine_model');

        $this->load->library('Search_header_data');
        $this->load->library('Upload_image');
    }

    /**
     * 对账单处理
     * @link export_server_handle/compact_statement_download_execute
     * @author Jolon
     */
    public function compact_statement_download_execute($id=null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1500M');
        $res = false;
        if (empty($id)){
            echo '处理ID不能为空';
            return $res;
        }

        $this->load->library('Filedirdeal');
        $this->load->library('Print_pdf_deal');
        $this->load->model('finance/Payment_order_pay_model');
        if(is_numeric($id) and $id > 0){
            $param_list = $this->data_center_model->get_items("id = " . $id);
            if(!isset($param_list[0]) || empty($param_list[0])){
                echo '查询不到要处理的数据';
                return $res;
            }
            $param_list = $param_list[0];
            $module_ch_name = $param_list['module_ch_name'];
            $params = json_decode($param_list['condition'], true);
            if (!is_array($params) || count($params) == 0){
                echo '参数不正确，请联系技术人员';
                return $res;
            }
        }else{
            $module_ch_name = 'CREATE_STATEMENT_PDF';
        }

        if($module_ch_name == 'STATEMENT_PDF_EXPORT'){// 对账单PDF文件下载
            $save_sub_dir           = 'PS_'.date('YmdHis').mt_rand(1000, 9999);
            $zip_file_name          = $save_sub_dir.'.zip';
            $template_dir           = 'statement_pdf_cache/'.$save_sub_dir;//  压缩包文件夹根目录
            $template_dir_full_path = get_export_path($template_dir);// 压缩包文件夹根目录绝对路径
            $zip_file_path          = $template_dir_full_path.$zip_file_name;// ZIP压缩包文件

            $limit = 100;
            $page = 1;
            $this->load->model('statement/Purchase_statement_model');

            $progress = 0; // 进度
            $download_file_list = [];

            do {
                $offsets = ($page - 1) * $limit;
                $is_last = true;
                $x       = 0;

                $result = $this->Purchase_statement_model->get_statement_list($params, $offsets, $limit, $page);
                if(!isset($result['data_list']['value']) or count($result['data_list']['value']) == 0){
                    $is_last = false;
                    break;
                }
                $values = $result['data_list']['value'];
                $values = array_column($values,'supplier_name','statement_number');

                unset($result);

                $success_list = $error_list = [];
                $origin_print_statement = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_statement_new');
                $origin_excel_statement = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_statement_excel');
                foreach($values as $statement_number => $supplier_name){

                    $statement = $this->Purchase_statement_model->get_statement($statement_number);
                    if($statement){
                        $statement       = format_price_multi_floatval($statement);
                        $key             = "print_statement_tmp";//缓存键
                        $key             = $key.'-'.$statement_number;

                        $print_statement = $origin_print_statement.'?statement_number='.$statement_number;
                        $header          = array('Content-Type: application/json');
                        $html_pdf        = getCurlData($print_statement,json_encode($statement, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果

                        $excel_statement = $origin_excel_statement.'?statement_number='.$statement_number;
                        $html_excel      = getCurlData($excel_statement,json_encode($statement, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果

                        $success_list[$supplier_name][$statement_number]['html_pdf'] = $html_pdf;// 对账单号+供应商名称作为文件名
                        $success_list[$supplier_name][$statement_number]['html_excel'] = $html_excel;// 对账单号+供应商名称作为文件名

                    }else{
                        $error_list[] = $statement_number.'：未获取到数据';
                    }

                    $x ++;
                }

                if($success_list){
                    // HTML 生成 PDF 文件
                    foreach($success_list as $supplier_name => $statement_data){
                        foreach($statement_data as $statement_number => $statement_value){
                            $html_pdf       = $statement_data[$statement_number]['html_pdf'];
                            $html_excel     = $statement_data[$statement_number]['html_excel'];
                            $css_file_name  = 'printStatementTemplate.css';
                            $file_save_path = get_export_path($template_dir.DIRECTORY_SEPARATOR.$supplier_name);// 生成供应商名称对应的目录
                            $fileNamePdf    = $file_save_path.$statement_number.$supplier_name.'.pdf';
                            $fileNameExcel  = $file_save_path.$statement_number.$supplier_name.".xls";

                            //设置PDF页脚内容
                            $footer = "<p style='text-align:center'>第<span>{PAGENO}</span>页,共<span>{nb}</span>页</p>";
                            $this->print_pdf_deal->writePdf($html_pdf,'',$fileNamePdf,'F', $css_file_name, '', $footer);

                            file_put_contents($fileNameExcel,$html_excel);// 输出EXCEL内容

                            $download_file_list[md5($fileNamePdf)] = $fileNamePdf;
                            $download_file_list[md5($fileNameExcel)] = $fileNamePdf;
                        }
                    }
                }
                if($error_list){
                    $fileName = $template_dir.'errors'.'.pdf';
                    $this->print_pdf_deal->writePdf(implode("<br/>",$error_list),'',$fileName,'F');
                    $download_file_list['errors'] = $fileName;
                }

                $data_status = 1;

                $page ++;
                $progress = $progress + $x;
                $this->data_center_model->updateCenterData($id, ['progress' => $progress,'data_status' => $data_status,'update_time' => date('Y-m-d H:i:s')]);

            } while ($is_last);

            if($download_file_list){
                $down_file_url  = $this->print_pdf_deal->create_zip_package($template_dir_full_path,$zip_file_path);

                if($down_file_url === true){
                    $HTTP_file_path = get_export_path_replace_host($template_dir_full_path,CG_SYSTEM_WEB_FRONT_IP).$zip_file_name;
                    $this->data_center_model->updateCenterData($id, ['file_name' => $zip_file_name, 'down_url' => $HTTP_file_path, 'data_status' => 3,'update_time' => date('Y-m-d H:i:s')]);
                }else{
                    $this->data_center_model->updateCenterData($id, ['file_name' => $zip_file_name, 'down_url' => $zip_file_path, 'data_status' => 3,'update_time' => date('Y-m-d H:i:s'),'remark' => '下载生成zip压缩包失败']);
                }
            }
        }
        elseif($module_ch_name == 'COMPACT_PDF_EXPORT'){// 批量下载合同
            $new_number = get_prefix_new_number('PC'.date('Ymd'),1,3);
            $save_sub_dir = str_replace('PC','',$new_number);
            if(empty($save_sub_dir)){
                echo '文件夹目录生成失败';
                return $res;
            }
            $this->load->model('compact/Compact_list_model','compact_model');

            $zip_file_name          = $save_sub_dir.'.zip';
            $template_dir           = 'compact_pdf_cache/'.$save_sub_dir;//  压缩包文件夹根目录
            $template_dir_full_path = get_export_path($template_dir);// 压缩包文件夹根目录绝对路径
            $zip_file_path          = $template_dir_full_path.$zip_file_name;// ZIP压缩包文件

            $limit  = 100;
            $page   = 1;

            $progress = 0; // 进度
            $download_file_list = [];

            do {
                $offsets = ($page - 1) * $limit;
                $is_last = true;
                $x       = 0;

                $result  = $this->Compact_list_model->get_compact_list($params, $offsets, $limit, $page);
                if(!isset($result['data_list']['value']) or count($result['data_list']['value']) == 0){
                    $is_last = false;
                    break;
                }
                $values = $result['data_list']['value'];

                $success_list = $error_list = [];
                $origin_print_compact_tax = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'tax_print_compact');
                $origin_print_compact_no_tax = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'no_tax_print_compact');
                foreach($values as $val){
                    if(!isset($val['supplier_name']) or !isset($val['compact_number']) or !isset($val['is_drawback'])){
                        $error_list[] = '必须参数缺失，程序异常，请联系技术处理';
                        continue;
                    }

                    $supplier_name  = $val['supplier_name'];
                    $compact_number = $val['compact_number'];
                    $is_drawback    = $val['is_drawback'];
                    $compact_data   = $this->compact_model->get_print_compact_data($compact_number);
                    if(empty($compact_data)){
                        $error_list[] = $compact_number.'：未获取到数据';
                        continue;
                    }

                    if($is_drawback == PURCHASE_IS_DRAWBACK_Y){
                        $key = "tax_compact";//缓存键
                        $css_file_name = 'taxRefundTemplate.css';
                        $print_compact = $origin_print_compact_tax;
                    }else{
                        $key = "ntax_compact";//缓存键
                        $css_file_name = 'nonRefundableTemplate.css';
                        $print_compact = $origin_print_compact_no_tax;
                    }
                    $print_compact .= '?compact_number='.$compact_number;
                    $this->rediss->setData($key.'-'.$compact_number, json_encode($compact_data));
                    $html = getCurlData($print_compact,'','get');//file_get_contents($print_compact);

                    $success_list[$supplier_name][$compact_number]['html'] = $html;
                    $success_list[$supplier_name][$compact_number]['css_file'] = $css_file_name;

                    $x ++;
                }

                if($success_list){
                    // HTML 生成 PDF 文件
                    foreach($success_list as $supplier_name => $compact_data){
                        foreach($compact_data as $compact_number => $compact_value){
                            $html           = $compact_data[$compact_number]['html'];
                            $css_file_name  = $compact_data[$compact_number]['css_file'];
                            $file_save_path = get_export_path($template_dir.DIRECTORY_SEPARATOR.$supplier_name);// 生成供应商名称对应的目录
                            $fileName       = $file_save_path.$compact_number.'.pdf';


                            //设置PDF页脚内容
                            $footer = "<p style='text-align:center'>第<span>{PAGENO}</span>页,共<span>{nb}</span>页</p>";
                            $this->print_pdf_deal->writePdf($html,'',$fileName,'F', $css_file_name, '', $footer);
                            $download_file_list[$compact_number] = $fileName;
                        }
                    }
                }
                if($error_list){
                    $fileName = $template_dir_full_path.'errors'.'.pdf';
                    $this->print_pdf_deal->writePdf(implode("<br/>",$error_list),'',$fileName,'F');
                    $download_file_list['errors'] = $fileName;
                }

                $data_status = 1;

                $page ++;
                $progress = $progress + $x;
                $this->data_center_model->updateCenterData($id, ['progress' => $progress,'data_status' => $data_status,'update_time' => date('Y-m-d H:i:s')]);

            } while ($is_last);

            if($download_file_list){
                $down_file_url  = $this->print_pdf_deal->create_zip_package(rtrim($template_dir_full_path,'/'),$zip_file_path);

                if($down_file_url === true){
                    $HTTP_file_path = get_export_path_replace_host($template_dir_full_path,CG_SYSTEM_WEB_FRONT_IP).$zip_file_name;
                    $this->data_center_model->updateCenterData($id, ['file_name' => $zip_file_name, 'down_url' => $HTTP_file_path, 'data_status' => 3,'update_time' => date('Y-m-d H:i:s')]);
                }else{
                    $this->data_center_model->updateCenterData($id, ['file_name' => $zip_file_name, 'down_url' => $zip_file_path, 'data_status' => 3,'update_time' => date('Y-m-d H:i:s'),'remark' => '下载生成zip压缩包失败']);
                }
            }
        }
        elseif($module_ch_name == 'COMPACT_LIST_EXPORT'){
            $template_file = 'HT_'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
            $template_path = get_export_path('compact_list_export') . $template_file;
            if (file_exists($template_path)) {
                unlink($template_path);
            }

            fopen($template_path, 'w');
            $fp = fopen($template_path, "a");
            $this->load->model('compact/Compact_list_model');

            $heads =['合同号','商品额','付款状态','是否退税','供应商','合同结算方式','操作人','操作时间','最新扫描件时间','是否上传扫描件'];
            foreach($heads as &$v){
                $v = iconv('UTF-8','GBK//IGNORE',$v);
            }
            fputcsv($fp,$heads);

            $limit = 1000;
            $page = 1;
            $progress = 0; // 进度

            do {
                $offsets = ($page - 1) * $limit;
                $is_last = true;
                $x       = 0;

                $result  = $this->Compact_list_model->get_compact_list($params, $offsets, $limit, $page);
                if(!isset($result['data_list']['value']) or count($result['data_list']['value']) == 0){
                    $is_last = false;
                    break;
                }
                $values = $result['data_list']['value'];
                $values = $this->Compact_list_model->formart_compact_list($values);

                foreach($values as $value){
                    try {
                        $row_list = [];
                        $row = [
                            $value['compact_number'],
                            $value['product_money'],
                            $value['payment_status'],
                            $value['is_drawback'],
                            $value['supplier_name'],
                            $value['settlement_method'],
                            $value['create_user_name'],
                            $value['create_time'],
                            $value['file_upload_time'],
                            $value['is_file_uploaded'],
                        ];
                        foreach ($row as $vvv) {
                            if(preg_match("/[\x7f-\xff]/",$vvv)){
                                $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                            }
                            if(is_numeric($vvv) && strlen($vvv) > 15){
                                $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                            }
                            $row_list[]=$vvv;
                        }
                        fputcsv($fp,$row_list);
                        unset($row_list);
                        unset($row);
                        $x ++;
                    }catch (Exception $e){}
                }

                $data_status = 1;
                $page ++;
                $progress = $progress + $x;
                $this->data_center_model->updateCenterData($id, ['progress' => $progress,'data_status' => $data_status,'update_time' => date('Y-m-d H:i:s')]);

            } while ($is_last);

            echo "好了\n";

            $java_result = $this->upload_image->doUploadFastDfs('file', $template_path, false);
            if ($java_result['code'] == 200) {
                $filepath = $java_result['data'];
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $filepath, 'data_status' => 3]);
            }else{
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $template_path, 'data_status' => 3]);
            }
        }
        elseif($module_ch_name == 'CREATE_STATEMENT_PDF'){
            $statement_number = $id;

            $this->load->library('Upload_image');
            $this->load->model('statement/Purchase_statement_model');
            $this->load->model('user/Purchase_user_model');


            $template_dir = 'statement_pdf_esign/';
            $origin_print_statement = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_statement_esign');
            $origin_print_statement .= '?statement_number=';
            $header = array('Content-Type: application/json');
            $css_file_name = 'printStatementTemplate.css';


            // 验证文件是否已经存在
            $file_save_path = get_export_path($template_dir.'/'.substr(md5($statement_number),0,3));
            $fileNamePdf = $file_save_path . $statement_number . '.pdf';
            if(file_exists($fileNamePdf)){
                unlink($fileNamePdf);// 更新则删除文件
            }


            // 生成对账单PDF文件
            $statement = $this->Purchase_statement_model->get_statement($statement_number);
            if ($statement) {
                $statement = format_price_multi_floatval($statement);
                $statement['create_time'] = date('Y/n/j',strtotime($statement['create_time']));// 转成日期格式

                // 对账联系人字段 create_user_name&create_user_phone
                $statement['create_user_name'] = $statement['statement_user_name'];

                $userInfo = $this->Purchase_user_model->get_user_info_by_user_id($statement['statement_user_id']);
                if(!empty($userInfo) and !empty($userInfo['phone_number'])){
                    $statement['create_user_phone'] = $userInfo['phone_number'];

                    $html_pdf = getCurlData($origin_print_statement.$statement_number, json_encode($statement, JSON_UNESCAPED_UNICODE), 'post', $header);//翻译结果


                    //设置PDF页脚内容
                    $footer = "<p style='text-align:center'>第<span>{PAGENO}</span>页,共<span>{nb}</span>页</p>";
                    $this->print_pdf_deal->writePdf($html_pdf, '', $fileNamePdf, 'F', $css_file_name, '', $footer);

                    if(!file_exists($fileNamePdf)){
                        echo '-文件生成失败<br/>';
                    }else{
                        $java_result = $this->upload_image->doUploadFastDfs('image', $fileNamePdf,false);
                        if(isset($java_result['code']) and $java_result['code'] == 200){
                            $this->Purchase_statement_model->purchase_db->where('statement_number',$statement_number)
                                ->update('purchase_statement',['filePath' => $java_result['data']]);

                            echo '-文件生成成功并上传JAVA服务器<br/>';
                        }else{
                            echo '-文件生成成功，但并上传JAVA服务器失败<br/>';
                        }
                    }
                }else{
                    echo '-对账员信息缺失<br/>';
                }
            } else {
                echo '-未获取到数据<br/>';
            }
        }
        else{
            $data_status = 3;
            $is_last = false;
            trigger_error("Undefined operator: " . $module_ch_name, E_USER_NOTICE);
        }

        echo '完结，撒花';
        return $res;
    }


    /**
     * 供应商余额表模块
     * @link export_server_handle/supplier_balance
     * @author Jolon
     */
    public function supplier_balance($id=null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1500M');
        $res = false;
        if (empty($id)){
            echo '处理ID不能为空';
            return $res;
        }

        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');

        $this->load->model('statement/Supplier_balance_order_model', 'balance_order');
        $param_list = $this->data_center_model->get_items("id = " . $id);
        if(!isset($param_list[0]) || empty($param_list[0])){
            echo '查询不到要处理的数据';
            return $res;
        }
        $param_list = $param_list[0];
        $module_ch_name = $param_list['module_ch_name'];
        $params = json_decode($param_list['condition'], true);
        if (!is_array($params) || count($params) == 0){
            echo '参数不正确，请联系技术人员';
            return $res;
        }

        if($module_ch_name == 'SUPPLIER_BALANCE_ORDER'){
            $template_file = 'sbo_'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
            $template_path = get_export_path('supplier_balance_order') . $template_file;
            if (file_exists($template_path)) {
                unlink($template_path);
            }

            fopen($template_path, 'w');
            $fp = fopen($template_path, "a");
            $this->load->model('compact/Compact_list_model');

            $heads = ['申请ID', '供应商','供应商代码', '采购主体', '发生时间', '调整金额', '申请人','申请时间', '申请备注', '审核人','审核时间', '审核备注', '审核状态'];
            foreach($heads as &$v){
                $v = iconv('UTF-8','GBK//IGNORE',$v);
            }
            fputcsv($fp,$heads);

            $limit = 1000;
            $page = 1;
            $progress = 0; // 进度

            do {
                $offsets = ($page - 1) * $limit;
                $is_last = true;
                $x       = 0;

                $result  = $this->balance_order->balance_order_list($params, $offsets, $limit, $page);
                if(!isset($result['values']) or count($result['values']) == 0){
                    $is_last = false;
                    break;
                }
                $values = $result['values'];

                foreach($values as $value){
                    try {
                        $row_list = [];
                        $row = [
                            $value['order_no'],
                            $value['supplier_name'],
                            $value['supplier_code'],
                            $value['purchase_name'],
                            $value['occurrence_time'],
                            $value['adjust_money'],
                            $value['create_user_name'],
                            $value['create_time'],
                            $value['create_note'],
                            $value['audit_user_name'],
                            $value['audit_time'],
                            $value['audit_note'],
                            $value['audit_status'],
                        ];
                        foreach ($row as $vvv) {
                            if(preg_match("/[\x7f-\xff]/",$vvv)){
                                $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                            }
                            if(is_numeric($vvv) && strlen($vvv) > 15){
                                $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                            }
                            $row_list[]=$vvv;
                        }
                        fputcsv($fp,$row_list);
                        unset($row_list);
                        unset($row);
                        $x ++;
                    }catch (Exception $e){}
                }

                $data_status = 1;
                $page ++;
                $progress = $progress + $x;
                $this->data_center_model->updateCenterData($id, ['progress' => $progress,'data_status' => $data_status,'update_time' => date('Y-m-d H:i:s')]);

            } while ($is_last);

            echo "好了\n";

            $java_result = $this->upload_image->doUploadFastDfs('file', $template_path, false);
            if ($java_result['code'] == 200) {
                $filepath = $java_result['data'];
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $filepath, 'data_status' => 3]);
            }else{
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $template_path, 'data_status' => 3]);
            }
        }
        elseif($module_ch_name == 'STATEMENT_EXPORT_CSV'){
            $template_file = 'ST_'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
            $template_path = get_export_path('statement/csv') . $template_file;
            if (file_exists($template_path)) {
                unlink($template_path);
            }

            fopen($template_path, 'w');
            $fp = fopen($template_path, "a");
            $this->load->model('statement/Purchase_statement_model');

            $heads = [
                '对账单号',
                '创建时间',
                '创建人',
                '作废人',
                '付款状态',
                '入库金额',
                '报损商品额',
                '运费',
                '加工费',
                '优惠额',
                '应付总金额',
                '请款商品额',
                '请款加工费',
                '请款代采佣金',
                '请款运费',
                '请款优惠额',
                '请款总额',
                '供应商名称',
                '供应商代码',
                '结算方式',
                '支付方式',
                '是否退税',
                '是否作废',
                '采购员',
                '是否上传扫描件',
                '上传扫描件时间',
                '对账单状态',
                '对账单来源',
                '对账人',
                '入库月份',
                '应付款时间'
            ];
            foreach($heads as &$v){
                $v = iconv('UTF-8','GBK//IGNORE',$v);
            }
            fputcsv($fp,$heads);

            $limit = 1000;
            $page = 1;
            $progress = 0; // 进度

            do {
                $offsets = ($page - 1) * $limit;
                $is_last = true;
                $x       = 0;

                // 获取数据
                $result = $this->Purchase_statement_model->get_statement_list($params, $offsets, $limit, $page);
                if(!isset($result['data_list']['value']) or count($result['data_list']['value']) == 0){
                    $is_last = false;
                    break;
                }
                $result['data_list']['value'] = $this->Purchase_statement_model->format_compact_list($result['data_list']['value']);// 格式化数据
                $values = $result['data_list']['value'];
                unset($result);

                foreach($values as $value){
                    try {
                        $row_list = [];
                        $row = [
                            $value['statement_number'],
                            $value['create_time'],
                            $value['create_user_name'],
                            '',// 没有作废人的
                            $value['statement_pdf_status'],
                            $value['total_instock_price'],
                            $value['total_loss_product_money'],
                            $value['total_freight'],
                            $value['total_process_cost'],
                            $value['total_discount'],
                            $value['total_pay_price'],
                            $value['in_pay_product_money'],
                            $value['in_pay_process_cost'],
                            $value['in_pay_commission'],
                            $value['in_pay_freight'],
                            $value['in_pay_discount'],
                            $value['in_pay_pay_price'],
                            $value['supplier_name'],
                            $value['supplier_code'],
                            $value['settlement_method'],
                            $value['pay_type'],
                            $value['is_drawback'],
                            '否',// 没有作废人的
                            $value['buyer_name']?implode(',',$value['buyer_name']):'',
                            $value['statement_pdf_status'],
                            $value['statement_pdf_time'],
                            $value['status_cn'],
                            $value['source_party_cn'],
                            $value['statement_user_name'],
                            $value['instock_month'],
                            $value['accout_period_time']

                        ];
                        foreach ($row as $vvv) {
                            if(preg_match("/[\x7f-\xff]/",$vvv)){
                                $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                            }
                            if(is_numeric($vvv) && strlen($vvv) > 15){
                                $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                            }
                            $row_list[]=$vvv;
                        }
                        fputcsv($fp,$row_list);
                        unset($row_list);
                        unset($row);
                        $x ++;
                    }catch (Exception $e){}
                }

                $data_status = 1;
                $page ++;
                $progress = $progress + $x;
                $this->data_center_model->updateCenterData($id, ['progress' => $progress,'data_status' => $data_status,'update_time' => date('Y-m-d H:i:s')]);

            } while ($is_last);

            echo "好了\n";

            $java_result = $this->upload_image->doUploadFastDfs('file', $template_path, false);
            if ($java_result['code'] == 200) {
                $filepath = $java_result['data'];
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $filepath, 'data_status' => 3]);
            }else{
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $template_path, 'data_status' => 3]);
            }
        }
        elseif($module_ch_name == 'EXPORT_DELIVERY'){
            $template_file = 'supplier_avg_delivery'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
            $template_path = get_export_path('supplier_avg/csv') . $template_file;
            if (file_exists($template_path)) {
                unlink($template_path);
            }

            fopen($template_path, 'w');
            $fp = fopen($template_path, "a");
            $this->load->model('supplier/Supplier_average_delivery_model');

            $heads = [
                '序号',
                '供应商代码',
                '供应商',
                '一级产品线',
                '合作状态',
                '结算方式',
                '统计月份',
                '国内仓交付天数',
                '海外仓交付天数',
                '国内仓10天交付率',
                '海外仓10天交付率'
            ];

            foreach($heads as &$v){
                $v = iconv('UTF-8','GBK//IGNORE',$v);
            }
            fputcsv($fp,$heads);

            $limit = 1000;
            $page = 1;
            $progress = 0; // 进度

            do {
                $offsets = ($page - 1) * $limit;
                $is_last = true;
                $x       = 0;

                // 获取数据
                $result = $this->Supplier_average_delivery_model->get_delivery_list($params, $offsets, $limit, $page);
                if(!isset($result['values']) or count($result['values']) == 0){
                    $is_last = false;
                    break;
                }
                $values = $result['values'];
                unset($result);

                foreach($values as $value){
                    try {
                        $row_list = [];
                        $row = [
                            'id' => $value['id'],
                            'supplier_code' => $value['supplier_code'],
                            'supplier_name' => $value['supplier_name'],
                            'linelist_cn_name' => $value['linelist_cn_name'],
                            'status' => $value['status'],
                            'supplier_settlement' => $value['supplier_settlement'],
                            'statis_month' => $value['statis_month'],
                            'ds_day_avg' => $value['ds_day_avg'],
                            'os_day_avg' => $value['os_day_avg'],
                            'ds_deliverrate' => $value['ds_deliverrate'],
                            'os_deliverrate' => $value['os_deliverrate'],
                        ];

                        foreach ($row as $vvv) {
                            if(preg_match("/[\x7f-\xff]/",$vvv)){
                                $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                            }
                            if(is_numeric($vvv) && strlen($vvv) > 15){
                                $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                            }
                            $row_list[]=$vvv;
                        }
                        fputcsv($fp,$row_list);
                        unset($row_list);
                        unset($row);
                        $x ++;
                    }catch (Exception $e){}
                }

                $data_status = 1;
                $page ++;
                $progress = $progress + $x;
                $this->data_center_model->updateCenterData($id, ['progress' => $progress,'data_status' => $data_status,'update_time' => date('Y-m-d H:i:s')]);

            } while ($is_last);

            echo "好了\n";

            $java_result = $this->upload_image->doUploadFastDfs('file', $template_path, false);
            if ($java_result['code'] == 200) {
                $filepath = $java_result['data'];
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $filepath, 'data_status' => 3]);
            }else{
                $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $template_path, 'data_status' => 3]);
            }
        }else{
            $data_status = 3;
            $is_last = false;
            trigger_error("Undefined operator: " . $module_ch_name, E_USER_NOTICE);
        }

        echo '完结，撒花';
        return $res;
    }

}