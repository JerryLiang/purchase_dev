<?php

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_model extends Api_base_model
{

    protected $_baseUrl; // 统一地址前缀
    protected $_listUrl; // 列表的路径
    protected $_supplierListUrl; // 供应商列表的路径
    protected $_updateUrl; // 更新的路径
    protected $_allUrl;  // 所有
    protected $_detailUrl; // 详情
    protected $_updateBuyerUrl; // 批量修改采购员
//    protected $_actionLogUrl; // 操作日志

    protected $_tableHeader = array(
        '序号', '供应商代码', '供应商名称', '结算方式', '支付方式', '产品线',
        '供应商等级', '采购员', '创建人', 'sku数量', '审核状态','审核备注', '店铺链接', '状态', '近三个月合作金额'
    );
    protected $_buyerTableHeader = array(
        'ID', '供应商代码', '供应商名称', '所属部门', '采购员ID', '采购员姓名', '是否可用',
    );
    protected $_detailTableHeader = array(
        '序号', '统一社会信用代码', '供应商名称', '供应商代码', '供应商等级', '供应商类型', '首次合作时间',
        '店铺链接','开发人','创建日期', '所在省', '所在市', '所在区', '一级产品线', '二级产品线', '三级产品线', '是否开发票',
        '供应商详细地址', '经营范围', '最近采购时间', '累计合作金额','供应商结算方式（一级）','供应商结算方式（二级）','资料是否齐全'
    );
    protected $_settlementtableHeader = array(
        'ID', '供应商代码', '供应商名称', '支付方式',
        '支付平台', '账户', '账户名', '状态', '主行', '具体支行名称', '账户类型', '省代码', '市代码', '证件号', '到账通知手机号',
        '添加人', '添加时间', '更新时间', '最后一次更新操作人','邮箱','币种','开户行地址','SWIFT代码'
    );

    protected $_contacttableHeader = array(
        '序号', '供应商代码', '联系人', '联系电话', 'FAX', '中文联系地址', '英文联系地址', '联系邮编', 'QQ', '微信',
        '旺旺', 'Skype', '性别', '邮箱', '法人代表'
    );

    protected $_imagetableHeader = array(
        'ID', '供应商代码', '供应商名称', '图片地址',
        '图片类型', '图片状态'
    );
    protected $_logHeader = array(
        '序号', '操作人', '明细'
    );

    //跨境宝
    protected $_crossBorder = array(
        0 => '否',
        1 => '是',
    );
    protected $_relationSupplier = array(
        '供应商名称', '合作状态','供应商来源','结算方式','支付方式','近30天入库金额','绑定的sku数量','关联类型','关联原因','操作'

    );

    // 字段-名称对应关系
    protected $_fieldTitleMap = array();



    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
        $this->load->helper('status_supplier');
    }

    /**
     * 供应商信息列表（分页接口）
     * @param array $params = array(
     *      'buyer' => string 采购员（模糊搜索）
     *      'supplier_name'    => string 供应商名（模糊搜索）
     *      'create_user_id' => int 创建人ID
     *      'supplier_settlement' => string 供应商结算方式
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

    public function getsupplier($params = array()) {
        $result = $this->_curlRequestInterface($this->_listSupplierData, $params, 'GET');
        return $result;
    }
    public function getList($params = array())
    {
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :intval($params['limit']);

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

        //字段内容替换
        $this->fileds_replacement($result);

        $data = $result['data'];
        $records = $data['data_list']['list'];

        $data['drop_down_box']['down_disable'] = isset($data['down_disable'])?$data['down_disable']:NULL;
        $data['drop_down_box']['down_tapDateStr_diff'] = isset($data['data_list']['drop_down_box']['down_tapDateStr_diff'])?$data['data_list']['drop_down_box']['down_tapDateStr_diff']:NULL;

        //静态下拉框数据(供应商等级)
        $data['drop_down_box']['down_level'] = supplier_level();

        //静态下拉框数据(供应商审核)
        $data['drop_down_box']['down_review'] = show_status(null);

        //静态下拉框数据(是否为跨境)
        $data['drop_down_box']['down_cross'] = supplier_cross_border();

        //下拉列表合作状态
        $data['drop_down_box']['down_cooperation_status'] = getCooperationStatus();
        //
       $data['drop_down_box']['down_is_diversion_status'] = getPurIsDiversionStatus();


        $data['drop_down_box']['down_supplier_source'] = isset($data['down_supplier_source'])?$data['down_supplier_source']:NULL;


        $data['drop_down_box']['down_is_complete'] = getComplete();
        //支付方式
        $data['drop_down_box']['payment_method'] = supplier_method();

        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $records,
                'drop_down_box' => $data['drop_down_box']
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
     * @desc 获取下拉供应商列表
     * @author Jackson
     * @parame array $parames 请求参数
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function get_supplier_list(array $params)
    {

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_supplierListUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        $data = $result['data'];
        $records = isset($data['list']) ? $data['list'] : $data;

        return array(
            'data_list' => array(
                'key' => ['供应商CODE', '供应商名称'],
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }

    /**
     * @desc 获取供应商 - 联系方式
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function supplier_contact()
    {
        // 2.调用接口
        $url = $this->_baseUrl . $this->_supplierListUrl;
        $result = $this->httpRequest($url, '', 'POST');

        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status']) || !isset($result['data'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (!empty($result['message'])) {
            $this->_errorMsg = $result['message'];
        }
        if (!$result['status']) {
            return null;
        }
        // End

        $data = $result['data'];
        $records = $data['list'];

        return array(
            'data_list' => array(
                'key' => ['供应商CODE', '供应商名称'],
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }

    /**
     * @desc 获取供应商 - 详情明细
     * @author Jackson
     * @Date 2019-01-22 16:01:00
     * @return array()
     **/
    public function get_details($params)
    {

        // 1.验证字段
        if (empty($params['supplier_id'])) {
            $this->_errorMsg = "supplier_id 不能为空";
            return;
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_detailUrl, $params, 'GET');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End
        //判断是否有下拉搜索列表
        check_down_box($result);
        $data = $result['data'];
        //静态下拉框数据(供应商等级)
        $data['drop_down_box']['down_level'] = supplier_level();

        //静态下拉框数据(供应商审核)
        $data['drop_down_box']['down_suppliertype'] = supplier_type();

        //静态下拉框数据(开票)
        $data['drop_down_box']['down_ticket'] = supplier_ticket();

        //静态下拉框数据(开票税点)
        $data['drop_down_box']['down_invoice_tax_rate'] = supplier_invoice_tax_rate();

        //静态下拉框数据(所属部门)
        $data['drop_down_box']['down_buyer_department'] = buyer_department();

        //下拉列表 跨境宝
        $data['drop_down_box']['down_is_cross_border'] = getCrossBorder();
        $data['drop_down_box']['supplier_source'] = $data['supplier_source'];   
        $data['drop_down_box']['is_complete'] = getComplete();//资料是否齐全
        $data['drop_down_box']['is_agent'] = getIsAgent();//资料是否齐全
        $data['drop_down_box']['is_postage'] = getIsPostage();//是否包邮
        //更新结算方式原因
        $data['drop_down_box']['down_settlement_change_res'] = $data['down_settlement_change_res'];

        $records = $data['basis_data'];
        $recordsBuyer = $data['buyer_data'];
        $payment_data = $data['payment_data'];
        $recordsContact = $data['contact_data'];
        $relationInfo = $data['relation_info'];
        $recordsImages = $data['images_data'];
        $historyImages = $data['history_image_data'];
        $settmentData = $data['settlement_data'];
        $suplier_enable_list = $data['suplier_enable_list'];
        $similar_suppliers   = $data['similar_suppliers'];
        //静态下拉框数据(支付方式)
        $downSettlement['down_payment_method'] = supplier_method();

        //静态下拉框数据(支付平台)
        $downSettlement['down_payment_platform'] = supplier_platform();

        //增加城市区域下拉列表（账务结算）
        $downSettlement['down_address'] = $data['drop_down_box']['down_address'];

        //获取供应商选择的结算方式
        $downSettlement['down_settlement_list'] = $data['down_settlement'];
       //开户行下拉框
        $downSettlement['payment_platform_bank'] = $result['data']['payment_platform_bank'];


        $relationDown['supplier_relation_type'] = $data['down_supplier_relation_type'];
        $relationDown['supplier_relation_reason'] = $data['down_supplier_relation_reason'];



        return array(
            'data_list' => array(
                'key' => $this->_detailTableHeader,
                'value' => $records,
                'drop_down_box' => $data['drop_down_box'],
            ),
            'data_buyer' => array(
                'key' => $this->_buyerTableHeader,
                'value' => $recordsBuyer,
            ),

            'data_contact' => array(
                'key' => $this->_contacttableHeader,
                'value' => $recordsContact,
                'drop_down_box' => null
            ),
            'relation_info' => array(
                'key' => $this->_relationSupplier,
                'value' => $relationInfo,
                'drop_down_box' => $relationDown
            ),
            'data_images' => array(
                'key' => $this->_imagetableHeader,
                'value' => $recordsImages,
                'drop_down_box' => null
            ),
            'suplier_enable_list' => array(
                'key' => [],
                'value' => $suplier_enable_list,
                'drop_down_box' => null
            ),
            'history_images' => array(
                'key' => $this->_imagetableHeader,
                'value' => $historyImages,
                'drop_down_box' => null
            ),
            'settlement_data' => array(
                'key'           =>['结算方式(一级)','结算方式(二级)'],
                'value'         =>$settmentData,
                'drop_down_box' =>null,
            ),
            'data_supplier_payment_info' => array(
                'key'   => [],
                'value' => $payment_data,
                'drop_down_box' =>$downSettlement,
            ),
            'similar_suppliers' =>array(
                'key' =>[],
                'value'=>$similar_suppliers,
                'drop_down_box'=>null


            )

        );
    }

    public function get_reject_reason(){
        return [
              '营业执照地址或法人变更，请上传最新的营业执照',
              '营业执照不清楚或者重要信息被遮挡',
              '营业执照名称与系统名称不一致',
              '链接1688店铺名称与系统名称不一致',
              '线下结算方式时，系统支行前要加上主行名称',
              '手机号和微信号不一致，请确认是否正确。如正确请备注后再重新提交',
              '合作告知函请上传至对应位置',
              '系统收款账号、收款人身份证号码、收款人手机号等信息与收款委托书不一致',
              '收款委托书还未到生效日期',
              '收款委托书须填写易佰英文抬头：YIBAI TECHNOLOGY LTD',
              '该供应商名下有含税在售SKU，请维护对公资料',
              '账期负向优化，请在钉钉审批通过后再提交'
            ];

    }

    /**
     * @desc 更新供应商信息
     * @author Jackson
     * @param array $params = array(
     *      'id' => int    required 记录ID
     *      ...  => string required 需要修改的字段
     * )
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function update_supplier_info(array $params)
    {
        // 1.验证字段
        if (empty($params['supplier_basic']['id'])) {
            return array(false, "id 不能为空");
        }
        list($status, $msg) = $this->validateParams($params, false);
        if (!$status) {
            return array(false, $msg);
        }

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_updateUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return array(false,$this->_errorMsg,[] );
        }
        // End

        return array($result['status'], $result['message']);
    }
    /**
     * 批量修改供应商采购员
     * @param type $params
     * @return type
     */
    public function get_supplier_batch_buyer($params){
        if(empty($params['ids'])){
            return array(false, "参数ids 不能为空");
        }
        //转化数组
        $ids= explode(',', $params['ids']);
        if(!is_array($ids) || empty($ids)){
             return array(false, "参数ids 格式不正确");
        }    
        $data_msg=[];
        /*
        foreach ($ids as $id) {
            $data=[
                'uid'=>$params['uid'],
                'supplier_basic'=>['id'=>$id],
                'supplier_buyer'=>[
                    ['buyer_type'=>1,'buyer_id'=>$params['buyer_dtc']],
                    ['buyer_type'=>2,'buyer_id'=>$params['buyer_oss']],
                    ['buyer_type'=>3,'buyer_id'=>$params['buyer_fba']]
                    ],
          ];
              // 1.调用接口
          $result = $this->_curlRequestInterface($this->_updateBuyerUrl, $data, 'POST');
          if (is_null($result)) {
            return array(false, $this->_errorMsg);
          } else{
             $data_msg= [$result['status'], $result['message']];
          } 
        } 
        */
        
        $data=[
            'uid'=>$params['uid'],
            'supplier_basic'=>['id'=>$params['ids']],
            'supplier_buyer'=>[
                ['buyer_type'=>1,'buyer_id'=>$params['buyer_dtc']],
                ['buyer_type'=>2,'buyer_id'=>$params['buyer_oss']],
                ['buyer_type'=>3,'buyer_id'=>$params['buyer_fba']],
                ['buyer_type'=>10,'buyer_id'=>$params['buyer_check']]

            ],
        ];
        $result = $this->_curlRequestInterface($this->_updateBuyerUrl, $data, 'POST');
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        } else{
            $data_msg= [$result['status'], $result['message']];
        }  
        return $data_msg; 
    }

    /**
     * @desc 供应商审核数据获取
     * @author Jackson
     * @param array $params = array(
     *      'supplier_code' => 供应商代码
     * )
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function supplier_review_detail(array $params)
    {
        // 1.验证字段
        if (!isset($params['supplier_id']) || empty($params['supplier_id'])) {
            $this->_errorMsg = "供应商 id 不能为空";
            return array(false, $this->_errorMsg);//指定返回错误
        }


        if (!isset($params['id']) || empty($params['id'])) {
            $this->_errorMsg = "审核记录 id 不能为空";
            return array(false, $this->_errorMsg);//指定返回错误
        }

        // 2.1调用接口
        $result1 = $this->_curlRequestInterface($this->_detailUrl, $params, 'GET');
        if (is_null($result1) or empty($result1['status'])) { //判断返回结果
            return array(false, $this->_errorMsg);//指定返回错误
        }

        // 2.2调用接口
        $result2 = $this->_curlRequestInterface($this->_reviewDetailUrl, $params, 'POST');
        if (is_null($result2) or empty($result2['status'])) {//判断返回结果
            return array(false, $this->_errorMsg);//指定返回错误
        }
        $result1['data']['basis_data'] = isset($result1['data']['basis_data'][0])?$result1['data']['basis_data'][0]:$result1['data']['basis_data'];
        $now_info = $result1['data'];
        $new_info = $result2['data'];


        $similar_contact_suppliers = $new_info['similar_contact_suppliers'];
        $similar_payment_suppliers = $new_info['similar_payment_suppliers'];
        $historyData               = $new_info['historyData'];
        $logData                   = $new_info['logData'];

        unset($new_info['similar_contact_suppliers']);
        unset($new_info['similar_payment_suppliers']);
        unset($new_info['logData']);
        unset($new_info['historyData']);



        //判断关联供应商
        if (!empty($new_info['delete_data']['relation_supplier'])) {
            $new_info['insert_data']['relation_supplier']=[];//代表全清空了

        }elseif(empty($new_info['insert_data']['relation_supplier'])&&!empty($now_info['relation_info'])){
            $new_info['insert_data']['relation_supplier'] = $now_info['relation_info'];
        }

        //优化提示
        if (isset($now_info['basis_data']['is_postage'])) {
            $now_info['basis_data']['is_postage'] = empty($now_info['basis_data']['is_postage'])?'':$now_info['basis_data']['is_postage'];

        }

        if (isset($new_info['basis_data']['is_postage'])) {
            $new_info['basis_data']['is_postage'] = empty($new_info['basis_data']['is_postage'])?'':$new_info['basis_data']['is_postage'];

        }

        if (isset($now_info['payment_data'])) {
            foreach ($now_info['payment_data'] as $is_tax => $payment_detail) {
                foreach ($payment_detail as $type=>$payment) {
                    $now_info['payment_data'][$is_tax][$type]['settlement_change_res'] = empty($now_info['payment_data'][$is_tax][$type]['settlement_change_res'])?'':$now_info['payment_data'][$is_tax][$type]['settlement_change_res'];

                }

            }

        }

        if (isset($new_info['change_data']['payment_data'])) {
            foreach ($new_info['change_data']['payment_data'] as $is_tax => $payment_detail) {
                foreach ($payment_detail as $type=>$payment) {
                    $new_info['change_data']['payment_data'][$is_tax][$type]['settlement_change_res'] = empty($new_info['change_data']['payment_data'][$is_tax][$type]['settlement_change_res'])?'':$new_info['change_data']['payment_data'][$is_tax][$type]['settlement_change_res'];

                }

            }

        }




        // 组装下拉框数据
        $down_list['data']['down_buyer']       = $now_info['down_buyer'];
        $down_list['data']['down_create_user'] = $now_info['down_create_user'];
        $down_list['data']['down_oneline']     = $now_info['down_oneline'];
        $down_list['data']['down_address']     = $now_info['down_address'];
        check_down_box($down_list);
        $down_list                    = $down_list['data']['drop_down_box'];
        $down_list['down_settlement'] = $now_info['down_settlement'];
        $down_list['down_department'] = $now_info['down_department'];

        $down_list['down_level']            = supplier_level();//静态下拉框数据(供应商等级)
        $down_list['down_suppliertype']     = supplier_type();//静态下拉框数据(供应商审核)
        $down_list['down_ticket']           = supplier_ticket();//静态下拉框数据(开票)
        $down_list['down_invoice_tax_rate'] = supplier_invoice_tax_rate();//静态下拉框数据(开票税点)
        $down_list['down_buyer_department'] = buyer_department();//静态下拉框数据(所属部门)
        $down_list['down_is_cross_border']  = getCrossBorder();//下拉列表 跨境宝
        $down_list['down_payment_method']   = supplier_method();//静态下拉框数据(支付方式)
        $down_list['down_payment_platform'] = supplier_platform();//静态下拉框数据(支付平台)
        $down_list['reject_reason'] = $this->get_reject_reason();//静态下拉框数据(驳回原因)
        $down_list['is_postage'] = getIsPostage();//静态下拉框数据(是否包邮)

        $down_list['down_supplier_relation_type'] = getSupplierRelationType();
        $down_list['down_supplier_relation_reason'] = getSupplierRelationReason();

        unset($now_info['down_buyer'],
            $now_info['down_create_user'],
            $now_info['down_department'],
            $now_info['down_oneline'],
            $now_info['down_address'],
            $now_info['down_settlement']);

        $result['now_info']  = $now_info;
        $result['new_info']  = $new_info;
        $result['down_list'] = $down_list;
        $result['similar_contact_suppliers']    = $similar_contact_suppliers;
        $result['similar_payment_suppliers']    = $similar_payment_suppliers;
        $result['historyData']    = $historyData;
        $result['logData']       = $logData;



        return array(
            'data_list' => $result,
        );
    }

    /**
     * @desc 更新供应商信息
     * @author Jackson
     * @param array $params = array(
     *      'id' => int    required 记录IDS
     *      ...  => string required 需要修改的字段
     * )
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function supplier_review(array $params)
    {
        // 1.验证字段
        if (!isset($params['id']) || empty($params['id'])) {
            $this->_errorMsg = "审核id 不能为空";
            return array(false, $this->_errorMsg);//指定返回错误
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_reviewUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);//指定返回错误
        }
        // End

        return array($result['status'], $result['message']);
    }

    /**
     * @desc 供应商信息禁用
     * @author Jackson
     * @param array $params = array(
     *      'id' => int    required 记录ID
     *      ...  => string required 需要修改的字段
     * )
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function supplier_disable(array $params)
    {
        // 1.验证字段
        if (!isset($params['id']) || empty($params['id'])) {
            return array(false, "id 不能为空");
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_disableUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        return array($result['status'], $result['message']);
    }

    /**
     * @desc 供应商信息启用
     * @author Jackson
     * @param array $params = array(
     *      'id' => int    required 记录ID
     *      ...  => string required 需要修改的字段
     * )
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function supplier_enable(array $params)
    {
        // 1.验证字段
        if (!isset($params['id']) || empty($params['id'])) {
            return array(false, "id不能为空");
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_enableUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        return array($result['status'], $result['message']);
    }

    /**
     * @desc 供应商支付帐号信息删除
     * @author Jackson
     * @param array $params = array(
     *      'id' => int    required 记录ID
     * )
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     * @Date 2019-01-30 14:01:00
     * @return array()
     **/
    public function delete_payment_account(array $params)
    {
        // 1.验证字段
        if (!isset($params['id']) || empty($params['id'])) {
            return array(false, "id不能为空");
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_delPaymentAccountUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End


        return array($result['status'], $result['message']);
    }

    /**
     * @desc 更新供应商信息字段验证
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
            'basic' => array(
                'id'                  => '供货商记录ID',
                'supplier_name'       => '供应商名称',
                'register_address'    => '注册地址',
                'register_date'       => '注册日期',
                'supplier_type'       => '公司类型',
                'store_link'          => '供应商链接',
                'invoice'             => '开票',
                'invoice_tax_rate'    => '开票税点',
                'is_cross_border'     => '是否支持跨境宝',
                'ship_province'       => '发货地址-所在省',
                'ship_city'           => '发货地址-所在市',
                'ship_area'           => '发货地址-所在区',
                'ship_address'        => '发货地址-县乡街道等详细信息',
                //'settlement_type'     => '供应商结算方式（一级）',
                //'supplier_settlement' => '供应商结算方式（二级）',
                'shop_id'             => '1688店铺ID',
                'tap_date_str'        => '线上账期日期',
                'quota'               => '授信额度值，单位（分）',
                'surplus_quota'       => '可用授信额度值，单位（分）',
                'supplier_source'     =>'供应商来源',
                'supplier_level'      =>'供应商等级',
                'credit_code'         => '统一社会信用代码',
                'is_complete'         => '资料是否齐全',///先不用验证了
                'remark'              => '申请备注',
                'legal_person'        => '法人代表',
                'is_agent'            => '是否是代理商',
                'is_postage'          => '是否包邮',
                'enabale_remark'      => '启用原因',
            ),

            'settlement_check' => [
                  'settlement_type'     => '供应商结算方式（一级）',
                  'supplier_settlement' => '供应商结算方式（二级）',
            ],

            //供应商联系方式字段
            'contact'    => array(
                'id'             => '联系人主键ID',
                'contact_person' => '联系人',
                'mobile'         => '手机号码',
                'contact_number' => '联系电话',
                'micro_letter'   => '微信号',
                'qq'             => 'QQ',
                'email'          => '电子邮箱',
            ),

            //部门及采购员字段
         /*   'buyer'      => array(
                'buyer_type' => '采购员部门',
                'buyer_id'   => '采购员ID'
            ),*/

            //财务结算
            'settlement' => array(
                1 => array(
                    'payment_method'          => '支付方式',
                    'store_name'              => '线上支付宝-店铺名称',
                    'account_name'            => '线上支付宝-收款名称',
                    'account'                 => '线上支付宝-收款账号',
                ),
                2 => array(// 2.线下境内=对公支付
                    'payment_method'          => '支付方式',
                    'payment_platform_bank'   => '线下境内-开户行名称',
                    'payment_platform_branch' => '线下境内-开户行名称-支行',
                    'account_name'            => '线下境内-收款名称',
                    'account'                 => '线下境内-收款账号',
                ),
                3 => array(// 3.线下境外=对私支付
                    'payment_method'          => '支付方式',
                    'payment_platform'        => '线下境外-支付平台',
                    'payment_platform_bank'   => '线下境外-开户行名称-主行',
                    'payment_platform_branch' => '线下境外-开户行名称-支行',
                    'account_name'            => '线下境外-收款名称',
                    'account'                 => '线下境外-收款账号',
                    'id_number'               => '线下境外-收款人身份证',
                    'phone_number'            => '线下境外-收款人手机号'
                ),
                4 => array(// paypal
                    'payment_method'          => '支付方式',
                    'account_name'            => 'paypal-收款人姓名',
                    'account'                 => 'paypal-收款账号',
                    'email'                   => 'paypal-paypal邮箱',
                    'currency'                => 'paypal-币种'
                ),
                5 => array(// 银行公对公
                    'payment_method'          => '支付方式',
                    'payment_platform_bank'   => '银行公对公-银行名称',
                    'payment_platform_branch' => '银行公对公-分行名称',
                    'account_name'            => '银行公对公-账号名称',
                    'account'                 => '银行公对公-银行账号',
                    'bank_address'            => '银行公对公-开户行地址',
                    'currency'                => '银行公对公-币种',
                    'swift_code'              => '银行公对公-swift代码',
                ),
                6 => array(// p卡
                    'payment_method'          => '支付方式',
                    'account_name'            => 'p卡-收款名称',
                    'account'                 => 'p卡-收款账号',
                    'email'                   => 'p卡-Payoneer邮箱',
                    'currency'                => 'p卡-币种'
                ),
            ),

            //上传图字段
            'images' => array(
                1 => array('busine_licen' => '营业执照'),
                2 => array('busine_licen' => '营业执照','receipt_entrust_book' => '收款协议'),
                3 => array('busine_licen' => '营业执照','verify_book' => '纳税人证明','ticket_data' => '开票资料')
            ),
        );

        //判断主字段信息
        if (!isset($params['id']) || empty($params['id']) || !isset($params['supplier_code']) || empty($params['supplier_code'])) {
            //  return array(false, "id或supplier_code字段不能为空");
        }

        //每组字段说明
        $comment = array(
            'supplier_basic'      => '基础数据',
            'supplier_contact'    => '供应商联系方式',
            'supplier_buyer'      => '部门及采购员信息字段',
            'supplier_settlement' => '财务结算信息字段',
            'supplier_image'      => '附件信息字段',
            'settlement_check'    => '结算方式字段',
        );

        if (!empty($reqFields)) {
            foreach ($reqFields as $key_type => $fields) {
                $key_type = 'supplier_' . $key_type;
                if($key_type == 'supplier_basic'){
                    /*if($params[$key_type]['settlement_type'] != 34){// 非线上账期不需要传的值
                        if(isset($params['supplier_settlement'][1]['payment_method']) && $params['supplier_settlement'][1]['payment_method'] == 1){
                            unset($fields['tap_date_str'],$fields['quota'],$fields['surplus_quota']);
                        }else{
                            unset($fields['shop_id'],$fields['tap_date_str'],$fields['quota'],$fields['surplus_quota']);
                        }
                    }*/
                    if($params[$key_type]['supplier_source'] != 1){//供应商来源为国内时 需要传资料是否齐全
                        unset($fields['is_complete']);
                    }

                    if($params[$key_type]['supplier_source'] != 2){//供应商来源不为海外时 不需要传的值
                        unset($fields['legal_person'],$fields['is_agent']);
                    }

                    $supplier_basic = [];
                    $verify_files = ['store_link','credit_code','shop_id','tap_date_str','quota','surplus_quota','is_postage'];
                    if( $params['flag'] == 1){
                        array_push($verify_files,"enabale_remark");
                    }
                    foreach ($fields as $key_field => $key_name) {

                        if(!in_array($key_field,$verify_files)
                            ||
                            ($key_field == 'credit_code' && (isset($params['supplier_basic']['is_complete'])
                                    && $params['supplier_basic']['is_complete'] == 1)
                                && isset($params['supplier_basic']['supplier_source'])
                                && !in_array($params['supplier_basic']['supplier_source'],[2,3]))){
                            if (!isset($params[$key_type][$key_field]) || $params[$key_type][$key_field] === '') {
                                return array(false, $comment[$key_type] . "： $key_name 不能为空");
                            } else {
                                $supplier_basic[$key_field] = trim($params[$key_type][$key_field]);//去掉首尾空格
                            }
                        }else{
                            $supplier_basic[$key_field] = trim($params[$key_type][$key_field]);//去掉首尾空格
                        }

                    }
                    $params[$key_type] = $supplier_basic;
                }elseif(in_array($key_type,['supplier_contact','supplier_buyer'])){
                    if(!empty($params[$key_type])){
                        foreach($params[$key_type] as $key_2 => $value_2){
                            if(!is_array($value_2)) return array(false, $comment[$key_type] . "：参数错误");

                            foreach ($fields as $key_field => $key_name) {
                                if(in_array($key_field,['micro_letter','qq'])){
                                    if ((!isset($params[$key_type][$key_2]['micro_letter']) || $params[$key_type][$key_2]['micro_letter'] === '')
                                    and (!isset($params[$key_type][$key_2]['qq']) || $params[$key_type][$key_2]['qq'] === '')) {
                                        return array(false, $comment[$key_type]. "：微信号和QQ必填一个");
                                    }else{
                                        $params[$key_type][$key_2][$key_field] = trim($params[$key_type][$key_2][$key_field]);//去掉首尾空格
                                    }
                                }else{
                                    $not_validate_field_arr = ['contact_number','email'];//邮箱  联系电话非必填
                                    if ((!isset($params[$key_type][$key_2][$key_field]) || $params[$key_type][$key_2][$key_field] === '') && !in_array($key_field,$not_validate_field_arr)) {
                                        return array(false, $comment[$key_type]. "：$key_name 不能为空");
                                    } else {
                                        $params[$key_type][$key_2][$key_field] = trim($params[$key_type][$key_2][$key_field]);//去掉首尾空格
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return array(true, 'OK');
    }

    /**
     * @desc 字段内容替换
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function fileds_replacement(array &$data)
    {
        if (isset($data['data']['data_list']['drop_down_box']['down_settlement'])){
            $down_settlement = $data['data']['data_list']['drop_down_box']['down_settlement'];
            $down_settlement = array_column($down_settlement,'settlement_name','settlement_code');
        }
        if (isset($data['data']['data_list']) && $item = &$data['data']['data_list']['list']) {

            foreach ($item as $key => $info) {

                //供应商等级
                $_supplierLevel = supplier_level($info['supplier_level']);
                if ($_supplierLevel) {
                    $item[$key]['supplier_level'] = $_supplierLevel;
                }

                //供应商结算方式
                if (isset($down_settlement)) {
                    if($info['supplier_settlement'] and strpos($info['supplier_settlement'],',') !== false){
                        $supplier_settlements = explode(',',$info['supplier_settlement']);
                        $_supplier_settlement = [];
                        foreach($supplier_settlements as $payment_method){
                            $_supplier_settlement[] = $down_settlement[$payment_method]??'';
                        }
                        $item[$key]['supplier_settlement'] = implode('/',$_supplier_settlement);
                    }else{
                        $_supplier_settlement = $down_settlement[$info['supplier_settlement']]??'';
                        if ($_supplier_settlement) {
                            $item[$key]['supplier_settlement'] = $_supplier_settlement;
                        }
                    }

//                    pr($down_settlement);exit;
//                    $item[$key]['supplier_settlement'] = isset($downSettlement[$info['supplier_settlement']]) ? $downSettlement[$info['supplier_settlement']] : $info['supplier_settlement'];
                }

                //支付方式
                if($info['payment_method'] and strpos($info['payment_method'],',') !== false){
                    $payment_methods = explode(',',$info['payment_method']);
                    $_paymentMethod = [];
                    foreach($payment_methods as $payment_method){
                        $_paymentMethod[] = getPayType($payment_method);
                    }
                    $item[$key]['payment_method'] = implode('/',$_paymentMethod);
                }else{
                    $_paymentMethod = getPayType($info['payment_method']);
                    if ($_paymentMethod) {
                        $item[$key]['payment_method'] = $_paymentMethod;
                    }
                }

                //申请状态
                $_reviewStatus = (!is_null($info['audit_status']) and $info['audit_status']>=0)? show_status($info['audit_status']) : $info['audit_status'];
                if ($_reviewStatus) {
                    $item[$key]['audit_status_name'] = $_reviewStatus;
                }

                //启用或禁用状态
//                $_eableStatus = enable_status($info['status']);
//                if ($_eableStatus) {
//                    $item[$key]['status'] = $_eableStatus;
//                }

            }

        }

    }

    /**
     * @desc 图片上传
     * @author Jackson
     * @Date 2019-01-22 16:01:00
     * @return array()
     **/
    public function do_upload(&$parames)
    {
        header("content-type:text/html;charset=utf-8");
        $FileDir = date("Y-m-d");
        $path = APP_UPLOAD . 'supplier';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $path = $path . "/" . $FileDir;
        //最终上传目录
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }


        $config['upload_path'] = $path;
        // 允许上传哪些类型
        $config['allowed_types'] = 'gif|png|jpg|jpeg|zip|rar|pdf|doc|docx|pptx|ppt|xlsx|csv|xls|gz';
        // 上传后的文件名，用uniqid()保证文件名唯一
        $config['file_name'] = uniqid();

        // 加载上传库
        $this->load->library('upload', $config);
//        print_r($this->upload->data());exit();

        //批量上传图片
        if (!empty($_FILES['MyFile'])) {
            foreach ($_FILES['MyFile']['name'] as $key => $item) {

                $fileKey = "MyFile";
                $fileKeyNew = "MyFile_{$key}";
                $_FILES[$fileKeyNew] = [
                    'name' => $_FILES[$fileKey]['name'][$key],
                    'type' => $_FILES[$fileKey]['type'][$key],
                    'tmp_name' => $_FILES[$fileKey]['tmp_name'][$key],
                    'error' => $_FILES[$fileKey]['error'][$key],
                    'size' => $_FILES[$fileKey]['size'][$key],
                ];

                if ($this->upload->do_upload($fileKeyNew)) {
                    $uploadData = $this->upload->data();
                    $realyPath = str_ireplace('\\', '/', $path);
                    $fileName = substr($realyPath, strpos($realyPath, ".com") + 4) . '/' . $uploadData['file_name'];
                    $parames['supplier_image'][$key] = $fileName;

                } else {
                    return array(false, $this->upload->display_errors());
                }

            }
        }

    }

    protected $_supplierListApi = "";

    /**
     * 获取供应商模糊查询
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     *  supplier_name=深圳 ==> supplier_code like "%深圳%" or supplier_name like "%深圳%";
     * @return mixed|array
     * @throws Exception
     */
    public function supplier_list($get)
    {
        $url = $this->_baseUrl . $this->_supplierListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }


    /**
     * 获取历史供应商
     * @author dean
     * @throws Exception
     */
    public function history_supplier_list($get)
    {
        $url = $this->_baseUrl . $this->_historySupplierListApi . "?" . http_build_query($get);

        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * @desc 获取创建人信息
     * @author Jackson
     * @parame array $params 参数
     * @Date 2019-02-26 21:01:00
     * @return array()
     **/
    public function get_create_user($params = array())
    {


        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_createNameApi, $params, 'GET');

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        $records = isset($result['data']) ? $result['data'] : '';
        return array(
            'data_list' => array(
                'key' => ['用户ID', '用户名称'],
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }

    /**
     * @desc
     * @author Jeff
     * @Date 2019/03/15 9:02
     * @param array $params
     * @return array
     * @return
     */
    public function get_supplier_quota($params = array())
    {
        $url = $this->_baseUrl . $this->_supplierQuotaApi;

        $rs = $this->_curlWriteHandleApi($url, $params, 'POST');

        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;

        return $rs;
    }


    /**
     * 获取操作日志列表
     * @param array $params = array(
     *      'id' => string  供应商id
     *      'offset' => int 第几页
     *      'limit' => int 分页大小
     * )
     *
     * @return array
     */
    public function get_op_log_list($params = array())
    {
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :intval($params['limit']);

        if (!isset($params['offset']) || intval($params['offset']) <= 0) {
            $params['offset'] = 1;
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_opLogUrl, $params, 'GET');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }

        $data = $result['data'];
        //$records = $data['data_list']['list'];
        return array(
            'data_list' => array(
                'key' => $this->_logHeader,
                'value' =>$data
            ),
            'page_data' => array(
                //'total' => $data['data_list']['count'],
                'offset' => intval($params['offset']),
                'limit' => intval($params['limit']),
                //'pages' => ceil(intval($data['data_list']['count']) / intval($params['limit'])),
            )
        );
    }


    /**
     * 获取操作日志列表
     * @param array $params = array(
     *      'id' => string  供应商id
     *      'offset' => int 第几页
     *      'limit' => int 分页大小
     * )
     *
     * @return array
     */
    public function get_op_log_pretty_list($params = array())
    {
        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_opLogPrettyUrl, $params, 'GET');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }

        $data = $result['data'];
        //$records = $data['data_list']['list'];
        return array(
            'data_list' => array(
                'key' => ['内容','操作人','操作时间'],
                'value' =>$data
            ),
        );
    }


    /**
     * @desc 获取支行信息
     * @author harvin
     * #date 2019-4-23
     * @param type $post
     * @return array
     */
    public function get_supplier_opening_bank($post){
        $url = $this->_baseUrl . $this->_openingBankApi;
        return $this->_curlReadHandleApi($url, $post,'POST');
    }

    //验证供应商名称是否和启用的供应商重复
    public function validate_supplier_name($post)
    {
        $url = $this->_baseUrl . $this->_validateSupplier;
        $result = $this->_curlReadHandleApi($url, $post, 'POST');
        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End
        return array($result['status'], $result['errorMess']);
    }

    /**
     * @desc: 删除图片信息
     * @param: $image_id     int     图片ID
     * @return ：  array  CODE： 0表示删除成功，1表示删除失败
     **/
    public function del_supplier_image($image_id)
    {
        try {
            // 后台删除供应商图片信息地址
            $url = $this->_baseUrl . $this->_delSupplierImageImageUrl;
            $result = $this->_curlReadHandleApi($url, array('image_id'=>$image_id), 'POST');
            if( isset($result['status']) && $result['status'] == 1 ) {
                return True;
            }
            throw new Exception("删除失败",505);
        }catch ( Exception $exp ) {

            throw new Exception($exp->getMessage(),$exp->getCode());
        }
    }

    /**
     * @desc: 获取图片信息
     * @param: $image_id     int|string     图片ID
     **/
    public function get_supplier_image($image_id) {

       try {

           $url = $this->_baseUrl . $this->_getSupplierImage;
           $result = $this->_curlReadHandleApi($url, array('image_id'=>$image_id), 'POST');
           if( !empty($result) && $result['status'] == 1 && !empty($result['data_list']))
           {
               return $result['data_list'];
           }
           return NULL;
       }catch ( Exception $exp )
       {
            throw new Exception( $exp->getMessage());
       }
    }
    
    /**
     * 应付款单列表页
     * @author ahrvin
     * @date 2019/7/3 
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_heaven_suppler($get){
         //调用服务层api
        $url = $this->_baseUrl . $this->_getheavenApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }


    /**
     * @desc 刷新供应商信息
     * @author dean
     */
    public function heaven_refresh_supplier($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_heavenRefreshSupplier;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }

    /**
     * @param $post
     * @return array|mixed
     */
    public function get_cross($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_getCrossApi;
        $result = parent::httpRequest($url, $post, 'POST');
        return $result;
    }

    /** 批量修改跨境宝
     * @param $post
     * @return array|mixed
     */
    public function update_supplier_cross($post){
        $url = $this->_baseUrl . $this->_updateCrossApi;
        $result = parent::httpRequest($url, $post, 'POST');
        return $result;
    }

    /** 根据供应商链接获取店铺ID （shop_url）
     * @param $get
     * @return array|mixed
     */
    public function get_shop_id($post){
        $url = $this->_baseUrl . $this->_getShopId;
        return parent::httpRequest($url, $post, 'POST');

    }

    /**
     * @desc 保存翻译信息
     * @author dean
     */
    public function save_trans_info($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_saveSupplierInfo;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }

    /**
     * @desc 展示
     * @author dean
     */
    public function show_trans_info($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_showSupplierInfo;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }

    /**
     * 手动刷新，1688是否支持跨境宝
     * @param $post
     * @return array|mixed
     * @throws Exception
     */
    public function refresh_cross_border($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_refreshCrossBorderUrl;
        return $this->request_appdal($params, $url, 'POST');
    }


    /**
     * @desc 供应商信息预禁用
     * @author Jackson
     * @param array $params = array(
     *      'id' => int    required 记录ID
     *      ...  => string required 需要修改的字段
     * )
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function pre_disable(array $params)
    {
        // 1.验证字段
        if (!isset($params['id']) || empty($params['id'])) {
            return array(false, "id 不能为空");
        }

        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_preDisableUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        return array($result['status'], $result['message']);
    }


    /**
     * @desc 获取关联供应商信息
     * @author harvin
     * #date 2019-4-23
     * @param type $post
     * @return array
     */
    public function get_relation_supplier_info($post){
        $url = $this->_baseUrl . $this->_supplierRelationUrl;
       // $result = parent::httpRequest($url, $post, 'POST');

        return $this->_curlReadHandleApi($url, $post, 'POST');

       // return $result;
    }
    /**
     * 手动刷新，1688是否支持跨境宝
     * @param $post
     * @return array|mixed
     * @throws Exception
     */
    public function update_supplier_level($params){
        //调用服务层api
        $url = $this->_baseUrl.$this->_updateSupplierLevelUrl;
        return $this->request_appdal($params, $url, 'POST');
    }


    /**
     * @desc 获取历史供应商信息
     * @author dean
     */
    public function get_history_payment_info($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_getHistoryPaymentInfoUrl;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }

    public function black_list($params = array())
    {
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize : intval($params['limit']);

        if (!isset($params['offset']) || intval($params['offset']) <= 0) {
            $params['offset'] = 1;
        }
        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_blackListUrl, $params, 'GET');
        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        //判断是否有下拉搜索列表
        check_down_box($result);

        //字段内容替换
        $this->fileds_replacement($result);

        $data = $result['data'];
        $records = $data['data_list']['list'];

        $data['drop_down_box']['down_disable'] = isset($data['down_disable']) ? $data['down_disable'] : NULL;

        //静态下拉框数据(供应商等级)
        $data['drop_down_box']['down_level'] = supplier_level();

        //静态下拉框数据(供应商审核)
        $data['drop_down_box']['down_review'] = show_status(null);

        //静态下拉框数据(是否为跨境)
        $data['drop_down_box']['down_cross'] = supplier_cross_border();

        //下拉列表合作状态
        $data['drop_down_box']['down_cooperation_status'] = getCooperationStatus();
        //
        $data['drop_down_box']['down_is_diversion_status'] = getPurIsDiversionStatus();


        $data['drop_down_box']['down_supplier_source'] = isset($data['down_supplier_source']) ? $data['down_supplier_source'] : NULL;


        $data['drop_down_box']['down_is_complete'] = getComplete();
        //支付方式
        $data['drop_down_box']['payment_method'] = supplier_method();

        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $records,
                'drop_down_box' => $data['drop_down_box']
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
     * @desc 供应商信息禁用
     * @param array $params = array(
     *      'id' => int    required 记录ID
     *      ...  => string required 需要修改的字段
     * )
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     * @Date 2019-01-21 16:01:00
     * @return array()
     **@author Jackson
     */
    public function supplier_opr_black_list(array $params)
    {

        //调用服务层api
        $url = $this->_baseUrl.$this->_supplierOprBlackListUrl;
        return $this->request_appdal($params, $url, 'POST');



    }

    public function black_list_detail(array $params)
    {


        // 2.调用接口
        $url = $this->_baseUrl . $this->_blackListDetail;
        return $this->_curlReadHandleApi($url, $params, 'POST');


    }

    public function modify_relation_supplier(array $params)
    {


        // 2.调用接口
        $url = $this->_baseUrl . $this->_modifyRelationSupplierUrl;
        return $this->_curlReadHandleApi($url, $params, 'POST');


    }


    public function show_all_relation_supplier(array $params)
    {


        // 2.调用接口
        $url = $this->_baseUrl . $this->_showAllRelationSupplierUrl;
        return $this->_curlReadHandleApi($url, $params, 'POST');


    }


    /**
     * 手动刷新，1688是否支持跨境宝
     * @param $post
     * @return array|mixed
     * @throws Exception
     */
    public function update_supplier_product_line($params){
        //调用服务层api
        $url = $this->_baseUrl.$this->_updateSupplierProductLineUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    public function add_supplier_users($datas){
        $url = $this->_baseUrl.$this->_add_supplier_users;
        return $this->request_appdal($datas, $url, 'POST');
    }
    public function upd_supplier_users($datas){
        $url = $this->_baseUrl.$this->_upd_supplier_users;
        return $this->request_appdal($datas, $url, 'POST');
    }
    public function del_supplier_users($params){
        $url = $this->_baseUrl.$this->_del_supplier_users;
        return $this->request_appdal($params, $url, 'POST');
    }
    public function show_supplier_users($params){
        $url = $this->_baseUrl.$this->_show_supplier_users."?uid=".$params['uid'];
        $result = $this->request_appdal($params, $url, 'POST');
        return $result;
    }

    public function audit_supplier_list($params = array()) {
        $result = $this->_curlRequestInterface($this->_auditSupplierListUrl, $params);
        return $result;
    }


    /**
     * @desc 获取历史供应商信息
     * @author dean
     */
    public function get_confirm_sku_info($post){
        //调用服务层api


        $result = $this->_curlRequestInterface($this->_getConfirmSkuInfoUrl, $post);

        return $result;

    }


    public function audit_supplier_level_grade_list($params){
        //调用服务层api
        $result = $this->_curlRequestInterface($this->_auditSupplierLevelGradeListUrl, $params);

        return $result;
    }

    public function get_audit_level_grade_log($params = array()) {

        $result = $this->_curlRequestInterface($this->_auditLevelGradeLogUrl, $params);

        return $result;
    }

    public function level_grade_review($params){
        //调用服务层api

        $result = $this->_curlRequestInterface($this->_levelGradeReviewUrl, $params);

        return $result;
    }

    public function modify_supplier_level_grade($params){

        $result = $this->_curlRequestInterface($this->_modifySupplierLevelGradeUrl, $params);
        return $result;
    }


    public function get_settlement_change($params = array()) {

        $result = $this->_curlRequestInterface($this->_getSettlementChangeUrl, $params);

        return $result;
    }


    public function supplier_visit_list($params = array())
    {
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :intval($params['limit']);

        if (!isset($params['offset']) || intval($params['offset']) <= 0) {
            $params['offset'] = 1;
        }
        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_visitListUrl, $params, 'GET');
        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        //判断是否有下拉搜索列表
        check_down_box($result);

        //字段内容替换
        $this->fileds_replacement($result);

        $data = $result['data'];
        $records = $data['data_list']['list'];

        $data['drop_down_box']['down_disable'] = isset($data['down_disable'])?$data['down_disable']:NULL;

        //静态下拉框数据(供应商等级)
        $data['drop_down_box']['down_level'] = supplier_level();

        //静态下拉框数据(供应商审核)
        $data['drop_down_box']['down_review'] = show_status(null);

        //静态下拉框数据(是否为跨境)
        $data['drop_down_box']['down_cross'] = supplier_cross_border();

        //下拉列表合作状态
        $data['drop_down_box']['down_cooperation_status'] = getCooperationStatus();
        //
        $data['drop_down_box']['down_is_diversion_status'] = getPurIsDiversionStatus();


        $data['drop_down_box']['down_supplier_source'] = isset($data['down_supplier_source'])?$data['down_supplier_source']:NULL;


        $data['drop_down_box']['down_is_complete'] = getComplete();
        //支付方式
        $data['drop_down_box']['payment_method'] = supplier_method();

        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $records,
                'drop_down_box' => $data['drop_down_box']
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
     * @desc 拜访供应商详情
     * @author dean
     */
    public function get_visit_detail_info($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_getVisitDetailInfo;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }

    //申请拜访供应商
    public function apply_visit($params){
        $url = $this->_baseUrl . $this->_applyVisitUrl;
        $result = $this->_curlReadHandleApi($url, $params,'POST');
        return $result;
    }


    public function visit_supplier_audit_list($params = array())
    {
        $result = $this->_curlRequestInterface($this->_visitSupplierAuditListUrl, $params);
        return $result;

    }

    //申请拜访供应商
    public function audit_visit_supplier($params)
    {
        $url = $this->_baseUrl . $this->_auditVisitSupplierUrl;
        $result = $this->_curlReadHandleApi($url, $params,'POST');
        return $result;
    }


    //上传拜访报告
    public function upload_visit_report($params)
    {
        $url = $this->_baseUrl . $this->_uploadVisitReportUrl;
        $result = $this->_curlReadHandleApi($url, $params,'POST');
        return $result;
    }

    //拜访操作日志

    public function get_visit_op_log_list($params = array())
    {

        $result = $this->_curlRequestInterface($this->_getVisitOpLogListUrl, $params);

        return $result;


    }





    /**
     * 拜访列表导出csv
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @throws Exception
     *
     */
    public function supplier_visit_list_csv($get)
    {
        $url = $this->_baseUrl . $this->_supplierVisitListCsvUrl . "?" . http_build_query($get);
        $res = $this->_curlReadHandleApi($url, "", 'GET');
//        print_r($res);exit;

        $this->load->library("CommonHelper");

        if(isset($res['status']) and $res['status'] == 0){
            return $res;
        }else{
            CommonHelper::arrayToCsv(
                isset($res['data_list']['key']) ? $res['data_list']['key'] : '',
                isset($res['data_list']['value']) ? $res['data_list']['value'] : '',
                '拜访列表导出-'.date('YmdH_i_s') . ".csv"
            );
        }
    }


    /**
     * 下载拜访报告
     * @author 叶凡立  20200730
     * @params  $params
     * @return  mixed
     */
    public function download_visit_report($params)
    {
        $url = $this->_baseUrl.$this->_downloadVisitReportUrl;
         $result = $this->request_http($params,$url,'GET',false);
         return $result;
    }








}