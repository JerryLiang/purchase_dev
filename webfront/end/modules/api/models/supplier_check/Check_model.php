<?php

/**
 * [function 供应商验货验厂]
 * @author Jackson
 * @param
 * @DateTime 2019/1/23
 */
class Check_model extends Api_base_model
{

    protected $_baseUrl; // 统一地址前缀
    protected $_listUrl; // 列表的路径
    protected $_supplierListUrl; // 供应商列表的路径
    protected $_updateUrl; // 更新的路径
    protected $_allUrl;  // 所有
    protected $_detailUrl; // 详情
    protected $_recordeUrl; // 获取指定数据地址

    protected $_tableHeader = array(
        '序号ID', '申请人用户ID', '验厂时间', '申请时间','验厂备注', '类别', '验厂次数', '供应商名', '验厂联系人', '联系电话', '联系地址',
        '采购单号', '验货次数', '验厂申请状态', '判定结果', '验厂原因', '验货原因', '检查费用'
    );

    protected $_editTableHeader = array(
        '类型', '部门', '期望时间', '供应商名称',
        '供应商代码', '验厂次数', '是否加急', '验厂联系人', '联系电话', '联系地址', '验厂备注', '单据类型', '采购单号'
    );

    protected $_materialTableHeader = array(
        '序号', '验厂申请编号', '文件类型', '文件名称'
    );

    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * 供应商信息列表（分页接口）
     * @param array $params = array(
     *      'status' => int 验厂申请状态
     *      'sku'    => string SKU（模糊搜索）
     *      'pur_number' => string 采购单（模糊搜索）
     *      'apply_user_id' => int 申请人
     *      'supplier_level' => int 供应商等级
     *      'status' => int 审核状态
     *      'is_cross_border' => int 跨境宝
     *      'first_product_line' => int 一级产品线
     *      'second_product_line' => int 二级产品线
     *      'third_product_line' => int 三级产品线
     *      'page' => int 第几页
     *      'limit' => int 分页大小
     * )
     *
     * @return array
     */
    public function getList($params = array())
    {
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize : intval($params['limit']);

        if (!isset($params['offset']) || intval($params['offset']) <= 0) {
            $params['offset'] = 1;
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_listUrl, $params, 'GET');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        //判断是否有下拉搜索列表
        check_down_box($result);

        $data = $result['data'];
        $records = $data['data_list']['list'];

        //静态下拉框数据(验厂申请状态)
        $data['drop_down_box']['down_status'] = supplier_check_status();

        //静态下拉框数据(检验结果)
        $data['drop_down_box']['down_result'] = supplier_check_result();

        //静态下拉框数据(类别)
        $data['drop_down_box']['down_type'] = supplier_check_type();

        //静态下拉框数据(是否加急)
        $data['drop_down_box']['down_urgent'] = supplier_is_urgent();

        //字段内容替换
        $this->fileds_replacement($records);

        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $records,
                'drop_down_box' => $data['drop_down_box'],
            ),
            'page_data' => array(
                'total' => $data['data_list']['count'],
                'offset' => intval($params['offset']),
                'limit' => intval($params['limit']),
                'pages' => ceil(intval($data['data_list']['count']) / intval($params['limit'])),

            )
        );
    }

    /**
     * @desc 创建验货验厂数据
     * @author Jackson
     * @parame array $params 参数
     * @Date 2019-01-23
     * @return array()
     **/
    public function create(array $params)
    {
        // 1.验证字段
        list($status, $msg) = $this->validateParams($params);
        if (!$status) {
            return array(false, $msg);
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_addUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        return array($result['status'], $result['message']);
    }

    /**
     * @desc 供应商验货验厂数据更新根据ID
     * @author Jackson
     * @parame array $params 参数
     * @Date 2019-01-23
     * @return array()
     **/
    public function confirm_update(array $parames)
    {
        // 1.验证字段
        list($status, $msg) = $this->validateParams($parames);
        if (!$status) {
            return array(false, $msg);
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_confirmUrl, $parames, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        return array($result['status'], $result['message']);
    }

    /**
     * @desc 供应商验货验厂(获取指定数据根据ID)
     * @author Jackson
     * @parame array $params 参数
     * @Date 2019-01-23
     * @return array()
     **/
    public function data_by_id($params = array())
    {

        //1.调用接口
        $result = $this->_curlRequestInterface($this->_recordeUrl, $params, 'GET');

        //2.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        $records = $result['data']['list'];

        return array(
            'data_list' => array(
                'key' => $this->_editTableHeader,
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }

    /**
     * @desc 获取验货验厂相关资料数据根据ID
     * @author Jackson
     * @parames array $params 请求参数
     * @Date 2019-01-23 16:01:00
     * @return array()
     **/
    public function get_material(array $params)
    {

        //1.验证字段
        if (!isset($params['check_id']) || !$params['check_id']) {
            return array(false, "验厂申请编号：check_id 不能为空");
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_meterialUrl, $params, 'GET');

        //3.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        $records = $result['data']['list'];

        //字段名替换
        $this->fileds_replacement($records);

        return array(
            'data_list' => array(
                'key' => $this->_materialTableHeader,
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }

    /**
     * @desc 供应商验货验厂信息字段验证
     * @author Jackson
     * @param array $params 参数
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     */
    public function validateParams(array &$params)
    {
        //查检字段是否为空
        $reqFields = array(
            //基础数据字段
            'supplier' => array(
                'check_type', 'supplier_code', 'check_times', 'group_id')
        );

        //如果验货类型采购单号必填(类别 1,验厂2，验货)
        if (isset($params['supplier']) && $params['supplier']['check_type'] == 2) {
            array_push($params['supplier'], 'pur_number');
        }

        if (!empty($reqFields)) {
            foreach ($reqFields as $key => $fields) {
                foreach ($fields as $field) {
                    if (!isset($params[$key][$field]) || $params[$key][$field] === '') {
                        return array(false, "供应商验货验厂： $field 不能为空");
                    } else {
                        $params[$key][$field] = trim($params[$key][$field]);//去掉首尾空格
                    }
                }
            }
        }
        return array(true, 'OK');
    }

    /**
     * @desc 数据导出
     * @author Jackson
     * @param array $params 参数
     * @return void()
     */
    public function export($params)
    {
        //过滤空数据
        $params = array_filter($params);
        //1.调用接口
        $result = $this->_curlRequestInterface($this->_exportUrl, $params, 'GET');

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        //导出数据到excel
        $head = [
            '采购员', '申请时间', 'PO号', '次数', '期望时间', '确认时间', '报告时间', '类别', '供应商名称',
            '供应商地址', '供应商联系人', '供应商电话', '判定结果', '评价', '改善措施', '检验次数', '验货原因', '检验费用'
        ];

        if (empty($result['data']['list']) || $result == null) {
            return false;
        }
        //字段替换
        $this->fileds_replacement($result['data']['list']);

        array_unshift($result['data']['list'], $head);
        CommonHelper::array2excel($result['data']['list'], '验货验厂数据' . date('Y年m月d日') . '.xls');
    }

    /**
     * @desc 验货验厂-相关数据导出（保证根目录权限可读写）
     * @author Jackson
     * @param array $params 参数
     * @return void()
     */
    public function batch_download(array $params)
    {

        $params['type'] = true;
        // 发送请求
        $url = $this->_baseUrl . $this->_meterialUrl;
        $url .= "?" . http_build_query($params);

        $result = $this->httpRequest($url, '', 'GET');

        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            $message = isset($result['message']) ? $result['message'] : $result['errorMess'];
            throw new Exception($message);
        }
        
        if (!$result['status']) {
            $this->_errorMsg = isset($result['message']) ? $result['message'] : $result['errorMess'];
            return null;
        }
        // End

        $records = $result['data']['list'];
        $fileList = array();
        if (!empty($records)) {
            foreach ($records as $key => $item) {
                if (isset($item['url']) && !empty($item['url'])) {
                    //保存文件到本地及获取文件名称
                    $fileList[] = CommonHelper::getFileInformation($item['url']);
                }
            }
        }
        //下载文件名
        $downloadName = 'supplier_files.zip';

        //压缩并下载文件
        if (!empty($fileList)) {
            CommonHelper::generateZipFile($fileList, $downloadName);
        }

    }

    /**
     * @desc 验货验厂-相关数据导出（保证根目录权限可读写）
     * @author Jackson
     * @param array $params 参数
     * @return void()
     */
    public function file_download(array $params)
    {
        $params['type'] = true;
        // 发送请求
        $url = $this->_baseUrl . $this->_meterialUrl;
        $url .= "?" . http_build_query($params);

        $result = $this->httpRequest($url, '', 'GET');

        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            $message = isset($result['message']) ? $result['message'] : $result['errorMess'];
            throw new Exception($message);
        }

        if (!$result['status']) {
            $this->_errorMsg = isset($result['message']) ? $result['message'] : $result['errorMess'];
            return null;
        }
        // End

        $records = $result['data']['list'];
        if (!empty($records) && isset($records[0]['url']) && !empty($records[0]['url'])) {
            CommonHelper::downloadFile($records[0]['url']);
        }

    }

    /**
     * @desc 字段内容替换
     * @author Jackson
     * @Date 2019-01-23 16:01:00
     * @return array()
     **/
    public function fileds_replacement(array &$data)
    {
        foreach ($data as $key => $item) {
            //类型判断
            if (isset($item['check_type'])) {
                $checkType = supplier_check_type($item['check_type']);
                $data[$key]['check_type'] = !is_array($checkType) ? $checkType : $item['check_type'];
            }

            //判定结果
            if (isset($item['judgment_results'])) {
                $result = supplier_check_result($item['judgment_results']);
                $data[$key]['judgment_results'] = !is_array($result) ? $result : $item['judgment_results'];
            }

            //判断资料类型
            if (isset($item['file_type'])) {
                $fileType = supplier_material_type($item['file_type']);
                $data[$key]['file_type'] = !is_array($fileType) ? $fileType : $item['file_type'];
            }

            //验厂申请状态
            if (isset($item['status']) && in_array('judgment_results', array_keys($item))) {
                $status = supplier_check_status($item['status']);
                $data[$key]['status'] = !is_array($status) ? $status : $item['status'];
            }

            //再次验货原因
            if (isset($item['review_reason'])) {
                $reviewTeason = supplier_check_review_reason($item['review_reason']);
                $data[$key]['review_reason'] = !is_array($reviewTeason) ? $reviewTeason : $item['review_reason'];
            }

        }
    }

}