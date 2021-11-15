<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/4
 * Time: 10:46
 * 计划部取消
 */
class Shipment_plan_cancel extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('user');
        $this->load->helper('status_order');
    }
// ****************** 申请明细start ******************
    /**
     * 计划部取消列表
     * /purchase_shipment/Shipment_plan_cancel/list
     * @author Manson
     */
    public function list()
    {
        try {
            //接收参数
            $params = $this->compatible('get');

            //加载列表service
            $this->load->service('shipment/PlanCancelListService');
            $this->plancancellistservice->setSearchParams($params);
            //过滤hook
            $this->plancancellistservice->setPreSearchHook([$this->plancancellistservice, 'hook_filter_params'], ['input' => $this->plancancellistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->plancancellistservice->setPreSearchHook([$this->plancancellistservice, 'hook_translate_params'], ['input' => &$this->plancancellistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->plancancellistservice->setPreSearchHook([$this->plancancellistservice, 'hook_format_params'], ['input' => &$this->plancancellistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->plancancellistservice->setAfterSearchHook([$this->plancancellistservice, 'translate'], ['input' => 'return', 'update' => 'none']);
            //返回查询结果
            $this->data = $this->plancancellistservice->execSearch();
//            pr($this->data);exit;
            //取配置项
            $cfg = $this->plancancellistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
//pr($this->data);exit;
            //取下拉
            $this->load->service('basic/DropdownService');

            $this->dropdownservice->setDroplist(
                $this->plancancellistservice->get_cfg()['droplist'],
                $is_override = true,
                $helper = ['status_order']
            );

            $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();

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

    /**
     * 导出
     * /purchase_shipment/Shipment_plan_cancel/export
     * @author Manson
     */
    public function export()
    {
        try
        {
            $post = $this->compatible();
            $this->load->service('shipment/PlanCancelExportService');
            $this->plancancelexportservice->setTemplate($post);
            $this->data['filepath'] = $this->plancancelexportservice->export('csv');
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
     * 取消详情
     * @author Manson
     */
    public function cancel_detail()
    {
        try
        {
            $params = $this->compatible('get');
            $this->load->service('shipment/PlanCancelService');
            $this->data['data'] = $this->plancancelservice->cancel_detail($params);
            //下拉
/*            $this->load->service('basic/DropdownService');
            $this->dropdownservice->setDroplist(
                ['province','return_season','freight_payment_type'],
                $is_override = true,
                $helper = ['status_order']
            );
            $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();*/
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
     * 采购驳回
     * @author Manson
     */
    public function purchase_reject()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('shipment/PlanCancelService');
            $this->data = $this->plancancelservice->purchase_reject($params);
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


}