<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/11/6
 * Time: 14:51
 */
class Purchase_return_after_storage_api extends MY_Controller
{

    public function __construct()
    {
        self::$_check_login = false;
        parent::__construct();
    }

    /**
     * 仓库批量驳回
     * Purchase_return_after_storage_api/wms_batch_reject
     * @author Manson
     */
    public function wms_batch_reject()
    {
        try
        {

            //接收数据
            $params = $this->compatible('post');
            $this->load->service('return/PurchaseConfirmService');
            $result = $this->purchaseconfirmservice->wms_reject($params);

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

}