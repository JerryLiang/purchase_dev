<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/4
 * Time: 10:46
 * 入库后退货管理
 */
class Purchase_return_after_storage extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('user');
        $this->load->helper('status_order');
    }
// ****************** 申请明细start ******************
    /**
     * 申请列表
     * /purchase/Purchase_return_after_storage/apply_list
     * @author Manson
     */
    public function apply_list()
    {
        try {
            //接收参数
            $params = $this->compatible('get');

            //加载列表service
            $this->load->service('return/ApplyReturnGoodsListService');
            $this->applyreturngoodslistservice->setSearchParams($params);
            //过滤hook
            $this->applyreturngoodslistservice->setPreSearchHook([$this->applyreturngoodslistservice, 'hook_filter_params'], ['input' => $this->applyreturngoodslistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->applyreturngoodslistservice->setPreSearchHook([$this->applyreturngoodslistservice, 'hook_translate_params'], ['input' => &$this->applyreturngoodslistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->applyreturngoodslistservice->setPreSearchHook([$this->applyreturngoodslistservice, 'hook_format_params'], ['input' => &$this->applyreturngoodslistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->applyreturngoodslistservice->setAfterSearchHook([$this->applyreturngoodslistservice, 'translate'], ['input' => 'return', 'update' => 'none']);
            //返回查询结果
            $this->data = $this->applyreturngoodslistservice->execSearch();
//            pr($this->data);exit;
            //取配置项
            $cfg = $this->applyreturngoodslistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
//pr($this->data);exit;
            //取下拉
            $this->load->service('basic/DropdownService');

            $this->dropdownservice->setDroplist(
                $this->applyreturngoodslistservice->get_cfg()['droplist'],
                $is_override = true,
                $helper = ['status_order']
            );

            $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();


//            //取编辑显示配置
//            $this->load->service('basic/UsercfgProfileService');
//
//            $result = $this->usercfgprofileservice->get_display_cfg('fba_pr_list');
//            $this->data['selected_data_list'] = $result['config'];
//            $this->data['profile'] = $result['field'];
//echo 123;exit;
            $this->data['status'] = 1;
            $code                 = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    public function format_list_data(&$data_list)
    {
        //开票信息
        $invoice_number_list = array_unique(array_column($data_list, 'invoice_number'));
        $invoice_map         = $this->get_invoice_map($invoice_number_list);
        //SKU集合 出口海关编码,
        $skus    = array_unique(array_column($data_list, 'sku'));
        $sku_map = $this->get_sku_map($skus);
        //报关信息
        $purchase_number_list  = array_unique(array_column($data_list, 'purchase_number'));
        $customs_clearance_map = $this->get_customs_clearance_map($purchase_number_list);

        foreach ($data_list as $key => &$item) {
            $_tag                 = sprintf('%s%s%s', $item['invoice_number'], $item['purchase_number'], $item['sku']);
            $ps_tag               = sprintf('%s_%s', $item['purchase_number'], $item['sku']);
            $item['invoice_info'] = $invoice_map[$_tag]??[];//开票信息
            if (!empty($item['invoice_info'])) {
                $total_invoiced_qty = array_sum(array_column($item['invoice_info'], 'invoiced_qty'));//总的开票数量
            }

            $item['customs_code'] = $sku_map[$item['sku']]['customs_code']??'';//出口海关编码
            $item['export_cname'] = $sku_map[$item['sku']]['export_cname']??'';//开票品名
            $item['declare_unit'] = $sku_map[$item['sku']]['declare_unit']??'';//开票单位

            //报关信息
            $item['customs_number']      = $customs_clearance_map[$ps_tag]['customs_number']??[];
            $item['customs_name']        = $customs_clearance_map[$ps_tag]['customs_name']??'';//
            $item['customs_unit']        = $customs_clearance_map[$ps_tag]['customs_unit']??'';//报关单位
            $item['customs_quantity']    = $customs_clearance_map[$ps_tag]['customs_quantity']??0;//sum_报关数量
            $item['no_customs_quantity'] = $item['upselft_amount'] - $item['customs_quantity'];//未报关数量
            $item['customs_type']        = $customs_clearance_map[$ps_tag]['customs_type']??'';//报关型号
            $item['total_amount']        = bcmul($item['unit_price']??0, $total_invoiced_qty??0, 2);//总金额 含税单价*已开票数量
            foreach ($item['invoice_info'] as &$invoice_info) {
                $invoice_info['audit_status'] = invoice_number_status($invoice_info['audit_status']);
            }
            if (isset($item['audit_status'])) {//导出
                $item['audit_status'] = invoice_number_status($item['audit_status']);
            }

//            invoice_number_status($item['']);
        }

    }


    /**
     * 申请退货导入
     * /purchase/Purchase_return_after_storage/apply_import
     * @author Manson
     */
    public function apply_import()
    {
        try {
            $params = $this->compatible('post');
            $this->load->service('return/ApplyReturnGoodsService');
            $this->data['data']   = $this->applyreturngoodsservice->import($params);
            $this->data['status'] = 1;
            $code                 = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }


    /**
     * 下载导入模板
     * /purchase/Purchase_return_after_storage/download_import_template
     * @author Manson
     */
    public function download_import_template()
    {
        try {
            $template_file = 'apply_return.csv';
            $down_host     = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $this->data['file_url'] = $down_host . 'download_csv/return/' . $template_file;
            $this->data['status'] = 1;
            $code = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 申请退货导出
     * /purchase/Purchase_return_after_storage/apply_export_list
     * @author Manson
     */
    public function apply_export_list()
    {
        try {
            ini_set('memory_limit', '1024M');
            set_time_limit(0);
            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');
            $params['ids'] = $this->input->get_post('ids');
            if (!empty($params['ids'])) {
                $result    = $this->m_financial_audit->get_financial_audit_list($params);
                $total     = $result['total_count']??0;
                $quick_sql = $result['quick_sql']??'';
            } else {
                $this->load->service('basic/SearchExportCacheService');
                $quick_sql = $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_FINANCIAL_AUDIT_LIST_SEARCH_EXPORT)->get();
                $total     = substr($quick_sql, 0, 10);
                $quick_sql = substr($quick_sql, 10);

                if (empty($quick_sql)) {
                    throw new \Exception(sprintf('请选择要导出的资源'));
                }
            }

            $file_name    = sprintf('财务审核列表_%s.csv', time());//文件名称
            $product_file = get_export_path() . $file_name;//文件下载路径
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file, 'w');
            $fp = fopen($product_file, "a");
            $this->load->classes('purchase/classes/FinancialAuditTemplate');
            $pick_cols = $this->FinancialAuditTemplate->get_default_template_cols();

            foreach ($pick_cols as $key => $val) {

                $title[$val['col']] = iconv("UTF-8", "GBK//IGNORE", $key);
            }

            $pick_cols = array_column($pick_cols, 'col');


            //将标题写到标准输出中
            fputcsv($fp, $title);
            if ($total >= 1) {
                $limit        = 1000;
                $total_page   = ceil($total / $limit);
                $time_cols    = ['audit_time', 'submit_time'];
                $tab_cols     = ['invoice_code_left', 'invoice_code_right', 'customs_number', 'customs_code'];
                $special_cols = ['product_name'];
                for ($i = 1; $i <= $total_page; ++$i) {

                    $offset = ($i - 1) * $limit;
                    $sql    = sprintf('%s LIMIT %s, %s', $quick_sql, $offset, $limit);
                    $result = $this->m_financial_audit->query_quick_sql($sql);
                    $this->format_list_data($result);
                    foreach ($result as $row) {
                        $new = [];
                        foreach ($pick_cols as $col) {
                            if (in_array($col, $special_cols)) {
                                $row[$col] = str_replace(["\r\n", "\r", "\n"], '', $row[$col]);//将换行
                                $row[$col] = str_replace(',', "，", $row[$col]);//将英文逗号转成中文逗号
                                $row[$col] = str_replace('"', "”", $row[$col]);//将英文引号转成中文引号
                            }
                            if (in_array($col, $time_cols)) {
                                $new[$col] = empty($row[$col]) || $row[$col] == '0000-00-00 00:00:00' ? '' : $row[$col] . "\t";
                            } elseif ($col == 'customs_number' && isset($row['customs_number'])) {
                                $new[$col] = implode(' ', $row['customs_number']);
                            } elseif (in_array($col, $tab_cols)) {
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
            $down_host     = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = $down_host . 'download_csv/' . $file_name;
            $this->success_json($down_file_url);
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 采购确认详情页
     * /purchase/Purchase_return_after_storage/apply_purchase_confirm_detail
     * @author Manson
     */
    public function apply_purchase_confirm_detail()
    {
        try
        {
            $params = $this->compatible();
            $this->load->service('return/ApplyReturnGoodsService');
            $this->data['data_list']['value']  = $this->applyreturngoodsservice->apply_purchase_confirm_detail($params);
            //下拉
            $this->load->service('basic/DropdownService');
            $this->dropdownservice->setDroplist(
                ['province','return_season','freight_payment_type'],
                $is_override = true,
                $helper = ['status_order']
            );
            $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            $code = 200;
            $this->data['status'] = 1;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 采购确认
     * /purchase/Purchase_return_after_storage/apply_purchase_confirm
     * @author Manson
     */
    public function apply_purchase_confirm()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('return/ApplyReturnGoodsService');
            $this->data = $this->applyreturngoodsservice->apply_purchase_confirm($params);
            $this->data['status'] = $this->data ? 1 : 0;
            $code = $this->data ? 200 : 500;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }


    /**
     * 采购驳回
     * /purchase/Purchase_return_after_storage/apply_purchase_reject
     * @author Manson
     */
    public function apply_purchase_reject()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('return/ApplyReturnGoodsService');
            $result = $this->applyreturngoodsservice->apply_purchase_reject($params);

            $this->data['status'] = $result ? 1 : 0;
            $code = $result ? 200 : 500;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 根据供应商获取联系信息
     * /purchase/Purchase_return_after_storage/get_supplier_contact
     * @author Manson
     */
    public function get_supplier_contact()
    {
        try
        {
            $params = $this->compatible();
            $this->load->service('return/ApplyReturnGoodsService');
            $this->data['data'] = $this->applyreturngoodsservice->get_supplier_contact($params);

            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 导出
     * /purchase/Purchase_return_after_storage/apply_export
     * @author Manson
     */
    public function apply_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('return/ApplyReturnGoodsExportService');
            $this->applyreturngoodsexportservice->setTemplate($post);
            $this->data['filepath'] = $this->applyreturngoodsexportservice->export('csv');
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

    }

    /**
     * 导出excel
     * /purchase/Purchase_return_after_storage/apply_export_excel
     * @author Manson
     */
    public function apply_export_excel()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('return/ApplyReturnGoodsExportService');
            $this->applyreturngoodsexportservice->setTemplate($post);
            $this->data['data_list'] = $this->applyreturngoodsexportservice->export('xlsx');
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
    }


// ****************** 申请明细end ******************
// ****************** 采购确认明细start ******************

    /**
     * 采购确认明细列表
     * @author Manson
     * /purchase/Purchase_return_after_storage/confirm_list
     */
    public function confirm_list()
    {
        try {
            //接收参数
            $params = $this->compatible('get');

            //加载列表service
            $this->load->service('return/PurchaseConfirmListService');
            $this->purchaseconfirmlistservice->setSearchParams($params);
            //过滤hook
            $this->purchaseconfirmlistservice->setPreSearchHook([$this->purchaseconfirmlistservice, 'hook_filter_params'], ['input' => $this->purchaseconfirmlistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->purchaseconfirmlistservice->setPreSearchHook([$this->purchaseconfirmlistservice, 'hook_translate_params'], ['input' => &$this->purchaseconfirmlistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->purchaseconfirmlistservice->setPreSearchHook([$this->purchaseconfirmlistservice, 'hook_format_params'], ['input' => &$this->purchaseconfirmlistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->purchaseconfirmlistservice->setAfterSearchHook([$this->purchaseconfirmlistservice, 'translate'], ['input' => 'return', 'update' => 'none']);
            //返回查询结果
            $this->data = $this->purchaseconfirmlistservice->execSearch();
//            pr($this->data);exit;
            //取配置项
            $cfg = $this->purchaseconfirmlistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
//pr($this->data);exit;
            //取下拉
            $this->load->service('basic/DropdownService');

            $this->dropdownservice->setDroplist(
                $this->purchaseconfirmlistservice->get_cfg()['droplist'],
                $is_override = true,
                $helper = ['status_order']
            );

            $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();


//            //取编辑显示配置
//            $this->load->service('basic/UsercfgProfileService');
//
//            $result = $this->usercfgprofileservice->get_display_cfg('fba_pr_list');
//            $this->data['selected_data_list'] = $result['config'];
//            $this->data['profile'] = $result['field'];
//echo 123;exit;
            $this->data['status'] = 1;
            $code                 = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $err = isset($errorMsg)?$errorMsg: '';
            $code == 200 or logger('error', sprintf("文件： %s 方法：%s 行：%d 错误：%s", __FILE__, __METHOD__, __LINE__, $err));
            $this->data['errorMess'] = $err;
            http_response($this->data);
        }
    }


    /**
     * 采购确认明细-采购驳回
     * /purchase/Purchase_return_after_storage/confirm_purchase_reject
     * @author Manson
     */
    public function confirm_purchase_reject()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('return/PurchaseConfirmService');
            $result = $this->purchaseconfirmservice->confirm_purchase_reject($params);

            $this->data['status'] = $result ? 1 : 0;
            $code = $result ? 200 : 500;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $err = isset($errorMsg)?$errorMsg: '';
            $code == 200 or logger('error', sprintf("文件： %s 方法：%s 行：%d 错误：%s", __FILE__, __METHOD__, __LINE__, $err));
            $this->data['errorMess'] = $err;
            http_response($this->data);
        }
    }


    /**
     * 采购经理审核
     * /purchase/Purchase_return_after_storage/confirm_purchasing_manager_audit
     * @author Manson
     */
    public function confirm_purchasing_manager_audit()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('return/PurchaseConfirmService');
            $result = $this->purchaseconfirmservice->confirm_purchasing_manager_audit($params);

            $this->data['status'] = $result ? 1 : 0;
            $code = $result ? 200 : 500;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $err = isset($errorMsg)?$errorMsg: '';
            $code == 200 or logger('error', sprintf("文件： %s 方法：%s 行：%d 错误：%s", __FILE__, __METHOD__, __LINE__, $err));
            $this->data['errorMess'] = $err;
            http_response($this->data);
        }
    }


    /**
     * 导出
     * /purchase/Purchase_return_after_storage/confirm_export
     * @author Manson
     */
    public function confirm_export()
    {
        try
        {
            $post = $this->compatible();
            $this->load->service('return/PurchaseConfirmExportService');
            $this->purchaseconfirmexportservice->setTemplate($post);
            $this->data['filepath'] = $this->purchaseconfirmexportservice->export('csv');
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
            $err = isset($errorMsg)?$errorMsg: '';
            $code == 200 or logger('error', sprintf("文件： %s 方法：%s 行：%d 错误：%s", __FILE__, __METHOD__, __LINE__, $err));
            $this->data['errorMess'] = $err;
            http_response($this->data);
        }

    }

    /**
     * 导出excel
     * @author Manson
     */
    public function confirm_export_excel()
    {
        try
        {
            $post = $this->compatible();
            $this->load->service('return/PurchaseConfirmExportService');
            $this->purchaseconfirmexportservice->setTemplate($post);
            $this->data['data_list'] = $this->purchaseconfirmexportservice->export('xlsx');
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
            $err = isset($errorMsg)?$errorMsg: '';
            $code == 200 or logger('error', sprintf("文件： %s 方法：%s 行：%d 错误：%s", __FILE__, __METHOD__, __LINE__, $err));
            $this->data['errorMess'] = $err;
            http_response($this->data);
        }
    }

    /**
     * 日志列表
     * /purchase/Purchase_return_after_storage/get_log_list
     * @author Manson
     */
    public function get_log_list()
    {
        try
        {
            $params = $this->compatible();
            $this->load->service('return/PurchaseConfirmService');
            $this->data['data_list']['value'] = $this->purchaseconfirmservice->get_log_list($params);

            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $err = isset($errorMsg)?$errorMsg: '';
            $code == 200 or logger('error', sprintf("文件： %s 方法：%s 行：%d 错误：%s", __FILE__, __METHOD__, __LINE__, $err));
            $this->data['errorMess'] = $err;
            http_response($this->data);
        }
    }




// ****************** 采购确认明细end ******************
// ****************** 退货跟踪start ******************
// ****************** 退货跟踪end ******************


}