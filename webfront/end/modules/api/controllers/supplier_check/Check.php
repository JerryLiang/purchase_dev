<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * [function 供应商验货验厂]
 * @author Jackson
 * @param
 * @DateTime 2019/1/23
 */
class Check extends MY_ApiBaseController
{
    /** @var Check_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier_check/Check_model');
        $this->_modelObj = $this->Check_model;
    }

    /**
     * @desc 供应商验货验厂管理分页列表
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function index()
    {
        $params = $this->_requestParams;

        $data = $this->_modelObj->getList($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @desc 创建验货验厂
     * @author Jackson
     * @Date 2019-01-23 16:01:00
     * @return array()
     **/
    public function create()
    {

        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->create($params);
        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        $this->sendData();

    }

    /**
     * @desc 供应商验货验厂指定ID更新
     * @author Jackson
     * @Date 2019-01-23 16:01:00
     * @return array()
     **/
    public function confirm()
    {
        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->confirm_update($params);

        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        $this->sendData();
    }

    /**
     * @desc 供应商验货验厂(获取单条数据根据ID)
     * @author Jackson
     * @Date 2019-01-23 16:01:00
     * @return array()
     **/
    public function data_by_id()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->data_by_id($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @desc 验货验厂数据导出
     * @author Jackson
     * @Date 2019-01-23 16:01:00
     * @return array()
     **/
    public function export_data()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
      
        $data = $this->_modelObj->export($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData();
        }
    }

    /**
     * @desc 验货验厂获取相关资料根据id
     * @author Jackson
     * @Date 2019-01-24
     * @return array()
     **/
    public function get_material()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_material($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @desc 验货验厂资料批量下载
     * @author Jackson
     * @Date 2019-01-24
     * @return array()
     **/
    public function batch_download()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;

        if (!isset($params['id']) || !$params['id']) {
            $this->_code = 0;
            $this->_msg = 'id不能为空';
            $this->sendData();
        }
        //将数组转换成字符串以","隔开
        if (is_array($params['id'])) {
            $params['id'] = implode(",", $params['id']);
        }

        $data = $this->_modelObj->batch_download($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        }
    }

    /**
     * @desc 验货验厂资料单个下载
     * @author Jackson
     * @Date 2019-01-24
     * @return array()
     **/
    public function download()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;

        if (!isset($params['id']) || !$params['id']) {
            $this->_code = 0;
            $this->_msg = 'id不能为空';
            $this->sendData();
        }

        $data = $this->_modelObj->file_download($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        }

    }
}