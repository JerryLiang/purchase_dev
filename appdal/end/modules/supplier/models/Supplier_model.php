<?php

/**
 * Created by PhpStorm.
 * 供应商相关操作
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Supplier_model extends Purchase_model
{

    protected $table_name = 'supplier';
    protected $detail_table_name = 'supplier_detail';
    protected $supplier_product_line_table_name = 'pur_supplier_analysis_product_line';
    protected $product_line_table_name = 'product_line';
    protected $audit_results_table_name = 'supplier_audit_results';
    protected $buyer_name = 'supplier_buyer';
    protected $pur_supplier_images = 'pur_supplier_images';
    protected $pur_supplier_cooperation_amount = 'pur_supplier_cooperation_amount';
    protected $pur_prod_supplier_category = 'pur_prod_supplier_category';
    protected $pur_prod_supplier_ext = 'pur_prod_supplier_ext';
    protected $audit_table = 'pur_supplier_update_log';
    protected $audit_level_grade_table = 'pur_supplier_level_grade_log';
    protected $supplier_visit_table    = 'pur_supplier_visit_apply';
    protected $supplier_visit_report_table    = 'pur_supplier_visit_report';
    private $_switch_disabled = true;
    private $_switch_disabled_tips = '该供应商已被禁用，不能进行该操作！';
    private $_check_not_completed = false;

    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName()
    {
        return $this->table_name;
    }

    /**
     * Supplier_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('supplier_buyer_model', '', false, 'supplier');
        $this->load->model('supplier_contact_model', '', false, 'supplier');
        $this->load->model('supplier_images_model', '', false, 'supplier');
        $this->load->model('supplier_product_line_model', '', false, 'supplier');
        $this->load->model('Supplier_payment_info_model', '', false, 'supplier');
        $this->load->helper('status_supplier');
        $this->load->model('Message_model');

    }

    /**
     * 供应商是否支持开票
     * @param  $supplier_code    string    供应商CODE
     * @author:luxu
     * @time:2020/2/25
     * @return Bool  True 支持开票，False 不支持开
     **/
    public function isSupplierInvoice($supplier_code)
    {
       $result = $this->purchase_db->from("supplier")->where("supplier_code",$supplier_code)->select("id,invoice")->get()->row_array();
       if( !empty($result) )
       {
           // 表示不支持开票
           if( $result['invoice'] == 1) {

               return False;
           }
           // 支持开票
           return True;
       }

       return False;
    }

    /**
     * 根据供应商支付方式
     * @param: $supplier_code      string   供应商CODE 编码
     *         $is_tax             string   是否退税
     *         $purchase_type_id   int      业务线
     * @author:luxu
     * @time:2020/02/25
     **/

    public function getSupplierPayment($supplier_code,$is_tax,$purchase_type_id,$search=NULL)
    {
        try{

            $where = array(
                'supplier_code' => $supplier_code,
                'is_tax'        => $is_tax,
                'purchase_type_id' => $purchase_type_id,
                'is_del' =>0
            );
            $query = $this->purchase_db->from("supplier_payment_info")->where($where);
            if( NULL != $search )
            {
                $query->select($search);
            }
            $result = $query->get()->row_array();
            return $result;
        }catch ( Exception $exp ){

            return [];
        }
    }

    /**
     * @desc 根据供应商code获取供应商名称
     * @param string $supplier_code
     * @return string
     * @author Jaden
     * @Date 2019/03/14 16:44
     */
    public function getSupplierNameBySupplierCode($supplier_code)
    {
        if (empty($supplier_code)) {
            return false;
        }

        $where = ['supplier_code' => $supplier_code];
        $supplier_info = $this->purchase_db->select('supplier_name')->where($where)->get($this->table_name)->row_array();
        return !empty($supplier_info) ? $supplier_info['supplier_name'] : '';
    }

    /**
     * 获取供应商基本信息
     * @param int $supplier_id 供应商ID
     * @param bool $append 带出供应商相关信息
     * @return array
     * @author Jolon
     */
    public function get_supplier_by_id($supplier_id, $append = true)
    {
        $supplier_info = $this->find_supplier_one($supplier_id, null, null, $append);
        return $supplier_info;
    }

    /**
     * 获取供应商基本信息
     * @param string $supplier_name 供应商名称
     * @param bool $append 带出供应商相关信息
     * @return array
     * @author Jolon
     */
    public function get_supplier_by_name($supplier_name, $append = true)
    {
        $supplier_info = $this->find_supplier_one(0, null, $supplier_name, $append);
        return $supplier_info;
    }

    /**
     * 获取供应商基本信息
     * @param string $supplier_code 供应商编码
     * @param bool $append 带出供应商相关信息
     * @return array
     * @author Jolon
     */
    public function get_supplier_info($supplier_code, $append = true)
    {

        $supplier_info = $this->find_supplier_one(0, $supplier_code, null, $append);
        if ($supplier_info) {
            // 合同模板显示的联系人信息
            if (isset($supplier_info['contact_list']) && !empty($supplier_info['contact_list']) && $supplier_info['contact_list'][0]) {
                $contact = $supplier_info['contact_list'][0];

                $supplier_info['compact_linkman'] = $contact['contact_person'];
                $supplier_info['compact_phone'] = $contact['contact_number'];
                $supplier_info['compact_email'] = $contact['email'];
            } else {
                $supplier_info['compact_linkman'] = '';
                $supplier_info['compact_phone'] = '';
                $supplier_info['compact_email'] = '';
            }
        }

        return $supplier_info;

    }


    /**
     * 获取供应商联系方式
     */
    public function get_supplier_contact_info($code)
    {
        if(!$code)return false;
        $data = $this->purchase_db->from('supplier_contact')
            ->select('contact_person, mobile')
            ->where('supplier_code = ', $code)
            ->get()->row_array();
        if(!$data || count($data) == 0)return false;
        return $data;
    }


    /**
     * 查找 一个 供应商
     * @param int $supplier_id 供应商ID
     * @param string $supplier_code 供应商编码
     * @param string $supplier_name 供应商名称
     * @param bool $append 带出供应商相关信息
     * @return array|bool
     */
    public function find_supplier_one($supplier_id = 0, $supplier_code = null, $supplier_name = null, $append = true)
    {
        $this->load->model('supplier/Supplier_update_log_model');
        $this->load->model('supplier/Supplier_payment_info_model');
        if (empty($supplier_id) && empty($supplier_code) && empty($supplier_name)) return false;
        if ($supplier_id) {
            $supplier_info = $this->purchase_db->where('id', $supplier_id)->get($this->table_name)->row_array();
        } elseif ($supplier_code) {
            $supplier_info = $this->purchase_db->where('supplier_code', $supplier_code)->get($this->table_name)->row_array();
        } else {
            $supplier_info = $this->purchase_db->where('supplier_name', $supplier_name)->get($this->table_name)->row_array();
        }

        if ($supplier_info && $append) {
            $supplier_code = $supplier_info['supplier_code'];
            $supplier_info['audit_list'] = $this->Supplier_update_log_model->get_latest_audit_result($supplier_code);// 最新更新记录
            $supplier_info['buyer_list'] = $this->supplier_buyer_model->get_buyer_list($supplier_code);// 采购员
            $supplier_info['contact_list'] = $this->supplier_contact_model->get_contact_list($supplier_code);// 联系人
            $supplier_info['images_list'] = $this->supplier_images_model->get_image_list($supplier_code);// 图片
            $supplier_info['product_line_list'] = $this->supplier_product_line_model->get_product_line_one($supplier_code);// 产品线
            $supplier_info['supplier_payment_info'] = $this->Supplier_payment_info_model->supplier_payment_info($supplier_code);//供应商财务结算信息
            $supplier_info['relation_supplier'] = $this->get_relation_supplier_detail($supplier_code,false);//供应商财务结算信息

        }

        return !empty($supplier_info) ? $supplier_info : [];

    }


    /**
     * 获取供应商 最新一个审核备注信息
     * @param     $supplier_code
     * @param int $type 备注类型  0.全部,1.审核通过,2.审核不通过
     * @return array|null
     */
    public function get_latest_audit_remark($supplier_code, $type = 2)
    {
        if ($type == 1) {// 审核通过
            $content_list = ['采购审核-审核通过', '供应链审核-审核通过', '财务审核-审核通过'];
        } elseif ($type == 2) {// 审核不通过
            $content_list = ['采购审核-审核不通过', '供应链审核-审核不通过', '财务审核-审核不通过'];
        } else {
            $content_list = ['采购审核-审核通过', '供应链审核-审核通过', '财务审核-审核通过', '采购审核-审核不通过', '供应链审核-审核不通过', '财务审核-审核不通过'];
        }
        $record = $this->purchase_db
            ->where('record_number', $supplier_code)
            ->where('record_type', 'supplier_update_log')
            ->where_in('content', $content_list)
            ->order_by('id  desc')
            ->get('operator_log')
            ->row_array();

        return $record ? $record : null;
    }

    /**
     * 判断供应商是否是 跨境供应商
     * @param string $supplier_code 供应商代码
     * @return bool
     * @author Jolon
     */
    public function is_cross_border($supplier_code)
    {
        $supplier_info = $this->find_supplier_one(0, $supplier_code, null, false);
        if ($supplier_info and $supplier_info['is_cross_border']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断供应商是否 是禁用状态
     * @param $supplier_code
     * @return bool
     */
    public function is_disabled($supplier_code)
    {
        if (!$this->_switch_disabled) {
            return false;
        }

        $supplierInfo = $this->get_supplier_info($supplier_code, false);
        if (empty($supplierInfo)) return false;
        if ($supplierInfo['status'] == IS_DISABLE) {
            return $this->_switch_disabled_tips;
        } else {
            return false;
        }
    }

    /**
     * 验证供应商信息是否完整的字段
     * @return array
     * @author Jolon
     */
    public function getReqiredFields()
    {
        //查检字段是否为空
        $reqFields = array(
            //基础数据字段
            'basic' => array(
                'id' => '供货商记录ID',
                'supplier_name' => '供应商名称',
                'register_address' => '注册地址',
                'register_date' => '注册日期',
                'supplier_type' => '公司类型',
                'store_link' => '供应商链接',
                'invoice' => '开票',
                'invoice_tax_rate' => '开票税点',
                'ship_province' => '发货地址-所在省',
                'ship_city' => '发货地址-所在市',
                'ship_area' => '发货地址-所在区',
                'ship_address' => '发货地址-县乡街道等详细信息',
                //'settlement_type'     => '供应商结算方式（一级）',
                'supplier_settlement' => '供应商结算方式',//（二级）
                'shop_id' => '1688店铺ID',
                'tap_date_str' => '线上账期日期',
                'quota' => '授信额度值，单位（分）',
                'surplus_quota' => '可用授信额度值，单位（分）',
            ),

            //供应商产品线
            'product_line' => array(
//                'first_product_line'  => '一级产品线',
//                'second_product_line' => '二级产品线',
//                'third_product_line'  => '三级产品线',
            ),

            //供应商联系方式字段
            'contact' => array(
                'id' => '联系人主键ID',
                'contact_person' => '联系人',
                'mobile' => '手机号码',
                'contact_number' => '联系电话',
//                'micro_letter'   => '微信号',
//                'qq'             => 'QQ',
                'email' => '电子邮箱',
            ),

            //部门及采购员字段
            'buyer' => array(
                1 => array('buyer_id' => '采购员ID', 'buyer_name' => '采购员名称'),
                2 => array('buyer_id' => '采购员ID', 'buyer_name' => '采购员名称'),
                3 => array('buyer_id' => '采购员ID', 'buyer_name' => '采购员名称'),
            ),

            //财务结算
            'settlement' => array(
                1 => array(
                    'payment_method' => '支付方式',
                    /*'store_name'              => '线上支付宝-店铺名称',
                    'account_name'            => '线上支付宝-收款名称',
                    'account'                 => '线上支付宝-收款账号',*/
                ),
                2 => array(// 2.线下境内=对公支付
                    'payment_method' => '支付方式',
                    'payment_platform_bank' => '线下境内-开户行名称',
                    'payment_platform_branch' => '线下境内-开户行名称-支行',
                    'account_name' => '线下境内-收款名称',
                    'account' => '线下境内-收款账号',
                ),
                3 => array(// 3.线下境外=对私支付
                    'payment_method' => '支付方式',
                    'payment_platform' => '线下境外-支付平台',
                    'payment_platform_bank' => '线下境外-开户行名称-主行',
                    'payment_platform_branch' => '线下境外-开户行名称-支行',
                    'account_name' => '线下境外-收款名称',
                    'account' => '线下境外-收款账号',
//                    'id_number'               => '线下境外-收款人身份证',
                    'phone_number' => '线下境外-收款人手机号'
                ),
                4 => array(// paypal
                    'payment_method' => '支付方式',
                    'account_name' => 'paypal-收款人姓名',
                    'account' => 'paypal-收款账号',
                    'email' => 'paypal-paypal邮箱',
                    'currency' => 'paypal-币种'
                ),
                5 => array(// 银行公对公
                    'payment_method' => '支付方式',
                    'payment_platform_bank' => '银行公对公-银行名称',
                    'payment_platform_branch' => '银行公对公-分行名称',
                    'account_name' => '银行公对公-账号名称',
                    'account' => '银行公对公-银行账号',
                    'bank_address' => '银行公对公-开户行地址',
                    'currency' => '银行公对公-币种',
                    'swift_code' => '银行公对公-swift代码',
                ),
                6 => array(// p卡
                    'payment_method' => '支付方式',
                    'account_name' => 'p卡-收款名称',
                    'account' => 'p卡-收款账号',
                    'email' => 'p卡-Payoneer邮箱',
                    'currency' => 'p卡-币种'
                ),
            ),

            //上传图字段
            'images' => array(
                1 => array('busine_licen' => '营业执照'),
                3 => array('busine_licen' => '营业执照', 'collection_order' => '收款委托书', 'idcard_front' => '身份证正面', 'idcard_back' => '身份证反面'),
                2 => array('busine_licen' => '营业执照', 'verify_book' => '一般纳税人认定书', 'bank_information' => '开票资料')
            ),
        );

        //每组字段说明
        $comment = array(
            'basic' => '基础数据',
            'product_line' => '产品线',
            'contact' => '供应商联系方式',
            'buyer' => '部门及采购员信息字段',
            'settlement' => '财务结算信息字段',
            'images' => '附件信息字段',
        );

        return ['reqFields' => $reqFields, 'comment' => $comment];
    }


    /**
     * 供应商修改日志字段信息转换
     * @return array
     * @author Jolon
     */
    public function getReqiredFieldsForLog()
    {
        //查检字段是否为空
        $reqFields = array(
            //基础数据字段
            'basic' => array(
                'id' => '供货商记录ID',
                'supplier_source' => '供应商来源',
                'supplier_name' => '供应商名称',
                'register_address' => '注册地址',
                'register_date' => '注册日期',
                'supplier_type' => '公司类型',
                'store_link' => '供应商链接',
                'invoice' => '开票',
                'invoice_tax_rate' => '开票税点',
                'ship_province' => '发货地址-所在省',
                'ship_city' => '发货地址-所在市',
                'ship_area' => '发货地址-所在区',
                'ship_address' => '发货地址-县乡街道等详细信息',
//                'settlement_type'     => '供应商结算方式（一级）',
//                'supplier_settlement' => '供应商结算方式（二级）',
                'shop_id' => '1688店铺ID',
                'tap_date_str' => '线上账期日期',
                'quota' => '授信额度值，单位（分）',
                'surplus_quota' => '可用授信额度值，单位（分）',
                'supplier_level' => '供应商等级',
                'payment_method' => '支付方式',
                'shipping_method_id' => '供应商运输方式',
                'cooperation_type' => '合作类型',
                'payment_cycle' => '支付周期类型',
                'transport_party' => '运输承担方',
                'is_cross_border' => '是否是跨境宝',
                'credit_code' => '统一社会信用代码',
                'is_complete' => '资料是否齐全',
                'legal_person' => '法人代表',
                'is_agent' => '是否是代理商',
                'is_postage' => '是否包邮',
                'is_diversion' => '是否是临时转常规',
                'supplier_grade'=>'供应商评分'
            ),


            'settlement_check' => [
                'settlement_type' => '供应商结算方式（一级）',
                'supplier_settlement' => '供应商结算方式（二级）',
            ],

            //供应商产品线
            'product_line' => array(
                'supplier_name' => '供应商名称',
                'first_product_line' => '一级产品线',
                'second_product_line' => '二级产品线',
                'third_product_line' => '三级产品线',
            ),

            //供应商联系方式字段
            'contact' => array(
                'id' => '联系人主键ID',
                'contact_person' => '联系人',
                'mobile' => '手机号码',
                'contact_number' => '联系电话',
                'micro_letter' => '微信号',
                'qq' => 'QQ',
                'want_want' => '旺旺',
                'email' => '电子邮箱',
            ),

            //部门及采购员字段
            'buyer' => array(
                1 => array('buyer_id' => '采购员ID', 'buyer_name' => '采购员名称'),
                2 => array('buyer_id' => '采购员ID', 'buyer_name' => '采购员名称'),
                3 => array('buyer_id' => '采购员ID', 'buyer_name' => '采购员名称'),
            ),

            'payment_method' => [
                1 => '线上支付宝',
                2 => '线下境内',
                3 => '线下境外',
                4 => 'paypal',
                5 => '银行公对公',
                6 => 'p卡',
            ],

            //财务结算
            'payment_data' => array(
                0 => [//不含税
                    1 => [
                        'settlement_type' => '不含税_国内/FBA_一级结算方式',
                        'supplier_settlement' => '不含税_国内/FBA_二级结算方式',
                        'payment_method' => '不含税_国内/FBA_支付方式',
                        'account_name' => '不含税_国内/FBA_账号名称',
                        'account' => '不含税_国内/FBA_收款账号',
                        'currency' => '不含税_国内/FBA_币种',
                        'payment_platform_bank' => '不含税_国内/FBA_银行名称',
                        'bank_address' => '不含税_国内/FBA_开户地址',
                        'swift_code' => '不含税_国内/FBA_swift代码',
                        'email' => '不含税_国内/FBA_邮箱',
                        'store_name' => '不含税_国内/FBA_店铺名称',
                        'payment_platform_bank' => '不含税_国内/FBA_开户行名称',
                        'payment_platform_branch' => '不含税_国内/FBA_支行',
                        'payment_platform' => '不含税_国内/FBA_支付平台',
                        'phone_number' => '不含税_国内/FBA_手机号',
                        'id_number' => '不含税_国内/FBA_身份证号码',
                        'settlement_change_res' => '不含税_国内/FBA_结算方式变更原因',
                        'settlement_change_remark' => '不含税_国内/FBA_结算方式变更原因(备注)',
                    ],
                    2 => [
                        'settlement_type' => '不含税_海外_一级结算方式',
                        'supplier_settlement' => '不含税_海外_二级结算方式',
                        'payment_method' => '不含税_海外_支付方式',
                        'account_name' => '不含税_海外_账号名称',
                        'account' => '不含税_海外_收款账号',
                        'currency' => '不含税_海外_币种',
                        'payment_platform_bank' => '不含税_海外_银行名称',
                        'bank_address' => '不含税_海外_开户地址',
                        'swift_code' => '不含税_海外_swift代码',
                        'email' => '不含税_海外_邮箱',
                        'store_name' => '不含税_海外_店铺名称',
                        'payment_platform_bank' => '不含税_海外_开户行名称',
                        'payment_platform_branch' => '不含税_海外_支行',
                        'payment_platform' => '不含税_海外_支付平台',
                        'phone_number' => '不含税_海外_手机号',
                        'id_number' => '不含税_海外_身份证号码',
                        'settlement_change_res' => '不含税_海外_结算方式变更原因',
                        'settlement_change_remark' => '不含税_海外_结算方式变更原因(备注)',
                    ],

                ],
                1 => [
                    0 => [
                        'settlement_type' => '含税_一级结算方式',
                        'supplier_settlement' => '含税_二级结算方式',
                        'payment_method' => '含税_支付方式',
                        'account_name' => '含税_账号名称',
                        'account' => '含税_收款账号',
                        'currency' => '含税_币种',
                        'payment_platform_bank' => '含税_银行名称',
                        'bank_address' => '含税_开户地址',
                        'swift_code' => '含税_swift代码',
                        'email' => '含税_邮箱',
                        'store_name' => '含税_店铺名称',
                        'payment_platform_bank' => '含税_开户行名称',
                        'payment_platform_branch' => '含税_支行',
                        'payment_platform' => '含税_支付平台',
                        'phone_number' => '含税_手机号',
                        'id_number' => '含税_身份证号码',
                        'settlement_change_res' => '含税_结算方式变更原因',
                        'settlement_change_remark' => '含税_结算方式变更原因(备注)',
                    ]

                ],
//
//                1 => array(
//                    'payment_method' => '支付方式',
//                    'store_name' => '线上支付宝-店铺名称',
//                    'account_name' => '线上支付宝-收款名称',
//                    'account' => '线上支付宝-收款账号',
//                ),
//                2 => array(// 2.线下境内=对公支付
//                    'payment_method' => '支付方式',
//                    'payment_platform_bank' => '线下境内-开户行名称',
//                    'payment_platform_branch' => '线下境内-开户行名称-支行',
//                    'account_name' => '线下境内-收款名称',
//                    'account' => '线下境内-收款账号',
//                ),
//                3 => array(// 3.线下境外=对私支付
//                    'payment_method' => '支付方式',
//                    'payment_platform' => '线下境外-支付平台',
//                    'payment_platform_bank' => '线下境外-开户行名称-主行',
//                    'payment_platform_branch' => '线下境外-开户行名称-支行',
//                    'account_name' => '线下境外-收款名称',
//                    'account' => '线下境外-收款账号',
//                    'id_number' => '线下境外-收款人身份证',
//                    'phone_number' => '线下境外-收款人手机号'
//                ),
//                4 => array(// paypal
//                    'payment_method' => '支付方式',
//                    'account_name' => 'paypal-收款人姓名',
//                    'account' => 'paypal-收款账号',
//                    'email' => 'paypal-paypal邮箱',
//                    'currency' => 'paypal-币种'
//                ),
//                5 => array(// 银行公对公
//                    'payment_method' => '支付方式',
//                    'payment_platform_bank' => '银行公对公-银行名称',
//                    'payment_platform_branch' => '银行公对公-分行名称',
//                    'account_name' => '银行公对公-账号名称',
//                    'account' => '银行公对公-银行账号',
//                    'bank_address' => '银行公对公-开户行地址',
//                    'currency' => '银行公对公-币种',
//                    'swift_code' => '银行公对公-swift代码',
//                ),
//                6 => array(// p卡
//                    'payment_method' => '支付方式',
//                    'account_name' => 'p卡-收款名称',
//                    'account' => 'p卡-收款账号',
//                    'email' => 'p卡-Payoneer邮箱',
//                    'currency' => 'p卡-币种'
//                ),
            ),

            //上传图字段
            'images' => array(
                'busine_licen' => '营业执照',
                'verify_book' => '一般纳税人认定书',
                'bank_information' => '开票资料',
                'collection_order' => '收款委托书',
                'other_proof' => '其他证明',
                'idcard_front' => '身份证正面',
                'idcard_back' => '身份证反面',
                'company_reg' => '公司注册书',
                'tax_cer' => '税务证明文件',
                'cooperation_letter'=>'供应商合作函'
            ),
            'relation_supplier'=>array(
                'supplier_type'=>'关联类型',
                'supplier_reason'=>'关联原因',
                'supplier_type_remark'=>'关联类型备注',
                'supplier_reason_remark'=>'关联原因备注'

            ),
            'payment_info' => array(
                '0' => '财务结算',
            )
        );

        //每组字段说明
        $comment = array(
            'basic' => '基础数据',
            'product_line' => '产品线',
            'contact' => '供应商联系方式',
            'buyer' => '部门及采购员信息字段',
            'settlement' => '财务结算信息字段',
            'images' => '附件信息字段',
            'settlement_check' => '结算方式字段',
            'relation_supplier'=>'关联供应商字段',
        );

        return ['reqFields' => $reqFields, 'comment' => $comment];
    }


    /**
     * 验证供应商信息是否完整
     * @param $supplier_code
     * @return array
     * @author Jolon
     */
    public function is_not_completed($supplier_code)
    {
        $return = ['code' => false, 'message' => '', 'data' => ''];
        if (!$this->_check_not_completed) {
            $return['code'] = true;
            return $return;
        }
        $data_tmp = [];

        $reqFields = $this->getReqiredFields();
        $basic = $reqFields['reqFields']['basic'];
        $product_line = $reqFields['reqFields']['product_line'];
        $contact = $reqFields['reqFields']['contact'];
        $buyer = $reqFields['reqFields']['buyer'];
        $settlement = $reqFields['reqFields']['settlement'];
        $images = $reqFields['reqFields']['images'];
        $comment = $reqFields['comment'];

        try {
            $supplierInfo = $this->get_supplier_info($supplier_code);
            $buyer_list = $supplierInfo['buyer_list'];
            $contact_list = $supplierInfo['contact_list'];
            $images_list = $supplierInfo['images_list'];
            $supplier_payment_info = $supplierInfo['supplier_payment_info'];
            $product_line_list = $supplierInfo['product_line_list'];

            // 验证供应商基本信息
            if ($supplierInfo['settlement_type'] != 34) {// 非线上账期不需要传的值
                unset($basic['shop_id'], $basic['tap_date_str'], $basic['quota'], $basic['surplus_quota']);
            }
            if ($supplierInfo['invoice'] == 1) {
                unset($basic['invoice_tax_rate']);
            }
            foreach ($basic as $basic_key => $basic_name) {
                if (!isset($supplierInfo[$basic_key]) or empty($supplierInfo[$basic_key]) or $supplierInfo[$basic_key] == '') {
                    $data_tmp[] = $comment['basic'] . "- $basic_name 为空";
                }
            }
            // 验证联系人
            foreach ($contact_list as $contact_value) {
                foreach ($contact as $contact_key => $contact_name) {
                    if (!isset($contact_value[$contact_key]) or empty($contact_value[$contact_key]) or $contact_value[$contact_key] == '') {
                        $data_tmp[] = $comment['contact'] . "- $contact_name 为空";
                    }
                }
            }
            // 验证产品线
            foreach ($product_line as $product_line_key => $product_line_name) {
                if (!isset($product_line_list[$product_line_key]) or empty($product_line_list[$product_line_key]) or $product_line_list[$product_line_key] == '') {
                    $data_tmp[] = $comment['product_line'] . "- $product_line_name 为空";
                }
            }
            // 验证采购员
            $buyer_list = arrayKeyToColumn($buyer_list, 'buyer_type');
            foreach ($buyer as $buyer_type => $buyer_value) {
                $buyer_type_name = $buyer_type == 1 ? '国内仓' : ($buyer_type == 2 ? '海外仓' : 'FBA');
                if (!isset($buyer_list[$buyer_type])) {
                    $data_tmp[] = $comment['buyer'] . "类型[$buyer_type_name] 缺失";
                    continue;
                }
                $check_buyer_value = $buyer_list[$buyer_type];
                foreach ($buyer_value as $buyer_key => $buyer_name) {
                    if (!isset($check_buyer_value[$buyer_key]) or empty($check_buyer_value[$buyer_key]) or $check_buyer_value[$buyer_key] == '') {
                        $data_tmp[] = $comment['buyer'] . "类型[$buyer_type_name] - $buyer_name 为空";
                    }
                }
            }

            // 验证结算方式
            foreach ($supplier_payment_info as $is_tax => $item){
                foreach ($item as $business_line => $val){
                    $payment_account_list[$val['settlement_type']] = $val;
                }
            }

            foreach ($settlement as $settlement_type => $settlement_value) {
                if (!isset($payment_account_list[$settlement_type])) continue;// 只验证存在的支付方式
                $check_payment_value = $payment_account_list[$settlement_type];
                foreach ($settlement_value as $settlement_key => $settlement_name) {
                    if (!isset($check_payment_value[$settlement_key]) or empty($check_payment_value[$settlement_key]) or $check_payment_value[$settlement_key] == '') {
                        $data_tmp[] = $comment['settlement'] . "- $settlement_name 为空";
                    }
                }
            }

            // 验证图片类型
            $images_list = array_column($images_list, 'image_url', 'image_type');
            $payment_method_list = array_column($payment_account_list, 'payment_method');
            foreach ($images as $payment_method => $images_value) {
                if (!in_array($payment_method, $payment_method_list)) continue;// 只验证存在的支付方式的图片
                foreach ($images_value as $images_key => $images_name) {
                    if (!isset($images_list[$images_key]) or empty($images_list[$images_key]) or $images_list[$images_key] == '') {
                        $data_tmp[] = $comment['images'] . "- $images_name 为空";
                    }
                }
            }

            if ($data_tmp) {
                $return['data'] = $data_tmp;
            } else {
                $return['code'] = true;
            }

        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }
        return $return;
    }

    /**
     * 根据条件获取 供应商列表
     * @param     $params
     * @param int $offset 偏移量
     * @param int $limit 每页条数
     * @return mixed
     * @author Jolon
     */
    public function get_list($params, $offset = null, $limit = null)
    {
        $params = $this->table_query_filter($params);
        $query_builder = $this->purchase_db;
        if ($params) {
            $query_builder->where($params);
        }

        $list = $query_builder->get($this->table_name, $offset, $limit)->result_array();
        return $list;
    }

    /**
     * 获取供应商 收款银行账号信息
     * @param string $supplier_code 供应商代码
     * @param int $payment_method 支付方式:1.支付宝,2.对公支付，3.对私支付
     * @return array|mixed
     * @author Jolon
     */
/*    public function get_supplier_pay_account($supplier_code, $payment_method = null)
    {
        $account_list = $this->supplier_payment_account_model->get_account_list($supplier_code);// 支付账号

        // 获取 对公或对私的支付账号
        if ($account_list and $payment_method) {
            $account_list_tmp = [];
            foreach ($account_list as $key => $account) {
                if ($account['payment_method'] == $payment_method) {
                    $account_list_tmp = $account_list[$key];
                }
            }
            return $account_list_tmp;
        }

        return $account_list;
    }*/

    /**
     * 获取供应商名称
     * @param type $supplier_code
     * @return string
     * @author harvin
     */
    public function get_supplier_name($supplier_code)
    {
        $supplier = $this->purchase_db
            ->select('supplier_name')
            ->where('supplier_code', $supplier_code)
            ->get('supplier')
            ->row_array();
        if (empty($supplier)) {
            return '';
        }
        return isset($supplier['supplier_name']) ? $supplier['supplier_name'] : '';
    }

    /**
     * 合同获取汇款信息
     * @param string $supplier_code 供应商代码
     * @param int $payment_method 支付方式:1.支付宝,2.对公支付，3.对私支付
     * @return array|mixed
     * @author Jolon
     */
    public function get_supplier_remit_information($supplier_code, $is_tax, $business_line)
    {
//        $account = $this->get_supplier_pay_account($supplier_code, $payment_method);
        $this->load->model('Supplier_payment_info_model','Supplier_payment_info_model');
        $account = $this->Supplier_payment_info_model->check_payment_info($supplier_code,$is_tax,$business_line);

        if (empty($account)) {
            return '';
        }
        $remit_information = "收款账号：" . (isset($account['account']) ? $account['account'] : '') .
            "  户名：" . (isset($account['account_name']) ? $account['account_name'] : '');
        $remit_information .= " 开户行：" . (isset($account['payment_platform_branch']) ? $account['payment_platform_branch'] : '');// 合同模板只显示支行
//        if(empty($account['payment_platform_branch'])){
//            $remit_information .= " 开户行：" . (isset($account['payment_platform_bank']) ? $account['payment_platform_bank'] : '').(isset($account['payment_platform_branch']) ? $account['payment_platform_branch'] : '');
//        }else{
//            $remit_information .= " 开户行：" .(isset($account['payment_platform_bank']) ? $account['payment_platform_bank'] : '').(isset($account['payment_platform_branch']) ? $account['payment_platform_branch'] : '');
//        }

        return $remit_information;
    }

    /**
     * 获取供应商
     * @param srting $supplier_name
     * @param  $status 默认为null
     **@author harvin 2019-1-18
     */
    public function get_supplier_list($supplier_name, $status = null)
    {
        $builder = $this->purchase_db
            ->select('supplier_code,supplier_name,status')
            ->from('supplier');
        if (is_numeric($status)) $builder->where('status', $status);
        if (is_array($status)) $builder->where_in('status', $status);

        $builder->group_start();
        $builder->like('supplier_name', $supplier_name);
        $builder->or_like('supplier_code', $supplier_name);
        $builder->or_where('supplier_code', $supplier_name);
        $builder->or_where('supplier_name', $supplier_name);
        $builder->group_end();

        $builder->limit(300);

        $suoolier = $builder->get()->result_array();
        $data = [];
        $status_label = [// 显示供应商状态
            '1' => '正常',
            '2' => '禁用',
            '3' => '删除',
            '4' => '待审',
            '5' => '驳回'
        ];
        foreach ($suoolier as $vo) {
            if( NULL !== $status){

                $data[$vo['supplier_code']] = $vo['supplier_name'];
            }else {
                $data[$vo['supplier_code']] = $vo['supplier_name'] . '(' . (isset($status_label[$vo['status']]) ? $status_label[$vo['status']] : '') . ')';
            }
        }
        return $data;

    }

    /**
     * 获取供应商的结算方式
     * @param: $supplier_code   string   供应商CODE
     * @author:luxu
     * @time:2020/9/21
     **/
    public function get_supplier_payment($supplier_code){

        if(empty($supplier_code)){
            return NULL;
        }

        $payment_info = $this->purchase_db->select("info.supplier_code, info.payment_method, info.supplier_settlement,settle.settlement_name")
            ->from('pur_supplier_payment_info as info')
            ->join('pur_supplier_settlement as settle','info.supplier_settlement=settle.settlement_code','LEFT')
            ->where_in('info.supplier_code', $supplier_code)
            ->where('info.is_del', 0)
            ->get()
            ->result_array();

        $string = '';
        if(!empty($payment_info)){

            $string = implode(",",array_unique(array_column($payment_info,'settlement_name')));
        }
        return $string;
    }

    public function supplier_data_sum($params = [])
    {

        $params = $this->table_query_filter($params);

        if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
            // 当查找一个供应商时 带出其关联的供应商，通过code查询
            if (is_array($params['supplier_code'])) {
                $query_supplier_codes = $params['supplier_code'];
                if(count($params['supplier_code']) == 1){
                    $relation_supplier = $this->get_relation_supplier_detail(current($params['supplier_code']),false);
                    if($relation_supplier) $query_supplier_codes = array_merge($query_supplier_codes,array_keys($relation_supplier));
                }
            } else {
                $query_supplier_codes = [$params['supplier_code']];
                $relation_supplier = $this->get_relation_supplier_detail($params['supplier_code'],false);
                if($relation_supplier) $query_supplier_codes = array_merge($query_supplier_codes,array_keys($relation_supplier));
            }
        }

        $query_builder = $this->purchase_db;

        //查询字段
//        $fields = 'A.id,A.supplier_code,A.supplier_name,A.supplier_settlement,A.payment_method,A.supplier_level,A.supplier_source,A.create_user_name,A.sku_num,A.store_link,A.status,A.audit_status,A.search_status,C.linelist_cn_name';
        $fields = "count(distinct(A.supplier_code)) as numrows";
        $query_builder->select($fields)
            ->from($this->table_name . ' AS A')
            ->join($this->supplier_product_line_table_name . ' AS B', "A.supplier_code=B.supplier_code AND B.status=1", 'LEFT')
            ->join($this->product_line_table_name . " AS C", "B.first_product_line=C.product_line_id", 'LEFT')
            ->join('pur_supplier_payment_info AS D', "A.supplier_code=D.supplier_code AND D.is_del=0", 'LEFT')
            ->join($this->pur_supplier_cooperation_amount . " AS E", "A.supplier_code=E.supplier_code", 'LEFT');

        if (isset($query_supplier_codes) && !empty($query_supplier_codes)) {
            $query_builder->where_in('A.supplier_code', $query_supplier_codes);
        }
        if (isset($params['payment_method'])) {
            $query_builder->where('D.payment_method', $params['payment_method']);
        }
        $searchSupplierCode = [];
        if( isset($params['overseas_supplier_code']) ){

            $searchSupplierCode = array_merge($searchSupplierCode,$params['overseas_supplier_code']);

        }

        if( isset($params['domestic_supplier_code']) ){

            $searchSupplierCode = array_merge($searchSupplierCode,$params['domestic_supplier_code']);
        }
        if(!empty($searchSupplierCode)){

            if(count($searchSupplierCode)>2000){

                $domestic_supplier_code = array_chunk($searchSupplierCode,2000);
                $query_builder->where("( 1=1")->where_in('A.supplier_code',$domestic_supplier_code[0]);
                foreach($domestic_supplier_code as $domestic_supplier_code_value){

                    $query_builder->or_where_in('A.supplier_code',$domestic_supplier_code_value);
                }
                $query_builder->where(" 1=1)");
            }else{
                $query_builder->where_in('A.supplier_code', $searchSupplierCode);
            }
        }




        if( isset($params['reportloss_start']) && isset($params['reportloss_end'])){

            $query_builder->where(" A.supplier_code IN (
                        SELECT
                            supplier_code
                        FROM
                            pur_purchase_order AS orders
                        LEFT JOIN `pur_purchase_order_reportloss` AS `reportloss` ON `reportloss`.`pur_number` = `orders`.`purchase_number`
                        WHERE reportloss.status=4
                        GROUP BY
                            orders.supplier_code
                        HAVING
                            SUM(loss_totalprice) >= {$params['reportloss_start']}  AND SUM(loss_totalprice)<={$params['reportloss_end']}
                    )"
            );
        }

        if (isset($params['buyer'])) {
            if ($params['buyer']==0) {
                $query_builder->where("A.supplier_code NOT IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_type=1 GROUP BY supplier_code)");

            } else {
                $buyer_str = implode(',',$params['buyer']);
                $query_builder->where("A.supplier_code IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_id in ({$buyer_str}) AND pur_{$this->buyer_name}.status='1'and buyer_type  in (1,2,3) GROUP BY supplier_code)");

            }

        }

        if(isset($params['is_gateway']) && $params['is_gateway'] != NULL){

            if( $params['is_gateway'] == 1){
                $query_builder->where_in('A.is_gateway',SUGGEST_IS_GATEWAY_YES)->where('A.is_push_purchase',1);
            }else{
                $query_builder->where('(A.is_gateway',SUGGEST_IS_GATEWAY_NO)->or_where('A.is_push_purchase=2)');
            }
        }
        if (isset($params['create_user_id'])) {
            $query_builder->where_in('A.create_user_id', $params['create_user_id']);
        }
        if (isset($params['supplier_settlement'])) {

            if(is_array($params['supplier_settlement'])) {
                $query_builder->where_in('D.supplier_settlement', $params['supplier_settlement']);
            }else {
                $query_builder->where('D.supplier_settlement', $params['supplier_settlement']);
            }
        }
        if (isset($params['supplier_level'])&&!empty($params['supplier_level'])) {
            $query_builder->where_in('A.supplier_level', $params['supplier_level']);
        }
        if (isset($params['is_cross_border'])) {
            $query_builder->where('A.is_cross_border', $params['is_cross_border']);
        }
        if (isset($params['status'])) {
            //$query_builder->where('A.status',$params['status']);
            if(is_array($params['status'])){
                $query_builder->where_in('A.audit_status', $params['status']);
            }else{
                $query_builder->where('A.audit_status', $params['status']);
            }
        }
        if (isset($params['first_product_line'])) {
            $query_builder->where_in('B.first_product_line', $params['first_product_line']);
        }
        if (isset($params['second_product_line'])) {
            $query_builder->where('B.second_product_line', $params['second_product_line']);
        }
        if (isset($params['third_product_line'])) {
            $query_builder->where('B.third_product_line', $params['third_product_line']);
        }
        if (isset($params['cooperation_status'])) {
            $query_builder->where('A.status', $params['cooperation_status']);
        }

        if (isset($params['supplier_source'])) {
            $query_builder->where('A.supplier_source', $params['supplier_source']);
        }


        if (isset($params['is_diversion_status'])) {
            $query_builder->where('A.is_diversion_status', $params['is_diversion_status']);
        }

        if (isset($params['is_complete']) && is_numeric($params['is_complete'])) {
            $query_builder->where('A.is_complete', $params['is_complete']);
        }
        //有效sku数量
        if (isset($params['min_sku_num'])) {
            $query_builder->where('A.sku_num >=', intval($params['min_sku_num']));
        }
        if (isset($params['max_sku_num'])) {
            $query_builder->where('A.sku_num <=', intval($params['max_sku_num']));
        }

        if (!empty($params['cooperation_letter'])) {
            $query_builder->join("pur_supplier_images AS images", "A.supplier_code=images.supplier_code AND images.image_type='cooperation_letter'", 'LEFT');

            if ($params['cooperation_letter'] == 1) {
                $query_builder->where('images.image_url IS NOT NULL');

            } else {
                $query_builder->where('images.image_url IS  NULL');

            }


        }
        //关联供应商
        //关联供应商
        if (!empty($params['is_relation'])) {

            $query_builder->join("pur_relation_supplier AS relation", "A.supplier_code=relation.supplier_code", 'LEFT');



            if ($params['is_relation'] == 1) {
                $query_builder->where('relation.supplier_code is not null');

            } else {
                $query_builder->where("relation.supplier_code  is null");



            }


        }

        //备用下单次数
        if (!empty($params['min_order_num'])) {
            $query_builder->where('A.order_num >=', intval($params['min_order_num']));


        }


        if (!empty($params['max_order_num'])) {
            $query_builder->where('A.order_num <=', intval($params['max_order_num']));


        }


        if (isset($params['check'])&&!empty($params['check'])) {

            $query_builder->where("A.supplier_code IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_id = {$params['check']} AND pur_{$this->buyer_name}.status='1'  and buyer_type ='10' GROUP BY supplier_code)");



        }




        // 3.分页参数
        $pageSize = query_limit_range(isset($params['limit']) ? $params['limit'] : 0);
        $page = !isset($params['offset']) || intval($params['offset']) <= 0 ? 1 : intval($params['offset']);
        $offset = ($page - 1) * $pageSize;
        // 三个月合作金额搜索条件
        if ((isset($params['min_nineth_data']) && strlen($params['min_nineth_data']) > 0) || (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0)) {
            $params['min_nineth_data'] = $params['min_nineth_data'] ?? 0;
            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
            $end = date('Y-m-d H:i:s');
            $nineth_sql = "A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING (sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) >= {$params['min_nineth_data']})";
//            $query_builder->where(" D.calculate_date >= '{$start}' AND D.calculate_date  <= '{$end}' GROUP BY D.supplier_code HAVING sum(D.cooperation_amount) >= {$params['min_nineth_data']}");
        }
        if (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0) {
//            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
//            $end = date('Y-m-d H:i:s');
//            $query_builder->where("A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}");
            $nineth_sql .= " AND sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}";
        }
        if (isset($nineth_sql)) {
            $query_builder->where($nineth_sql . ')');
        }


        if (!empty($params['develop_source'])) {
            $query_builder->where('A.develop_source',$params['develop_source']);

        }
        if (!empty($params['platform_source'])) {
            $query_builder->where('A.platform_source',$params['platform_source']);

        }

        if (!empty($params['business_line'])) {
            $query_builder->where('A.business_line',$params['business_line']);

        }

        if (!empty($params['create_time_start'])) {
            $query_builder->where('A.create_time >=', $params['create_time_start']);


        }

        if (!empty($params['create_time_end'])) {
            $query_builder->where('A.create_time <=', $params['create_time_end']);


        }


        if (!empty($params['restart_date_start'])) {
            $query_builder->where('A.restart_date >=', $params['restart_date_start']);


        }

        if (!empty($params['restart_date_end'])) {
            $query_builder->where('A.restart_date <=', $params['restart_date_end']);


        }

        if( !empty($params['visit_times_min']) || !empty($params['visit_times_max'])){
            if (!empty($params['visit_times_min'])&&!empty($params['visit_times_max'])) {
                $visit_num_ext =  " count(*) >= {$params['visit_times_min']}  AND count(*) <={$params['visit_times_max']}";

            } elseif (!empty($params['visit_times_min'])&&empty($params['visit_times_max'])) {
                $visit_num_ext =  " count(*) >= {$params['visit_times_min']} ";

            } else {
                $visit_num_ext =  " count(*) <= {$params['visit_times_max']} ";

            }

            $query_builder->where(" A.supplier_code IN (
                      SELECT supplier_code FROM pur_supplier_visit_apply GROUP BY supplier_code
                      HAVING ".$visit_num_ext.") "
            );
        }

        //备用次数查询
        if( !empty($params['backup_num_min']) || !empty($params['backup_num_max'])){
            if (!empty($params['backup_num_min'])&&!empty($params['backup_num_max'])) {
                $backup_num_ext =  " count(*) >= {$params['backup_num_min']}  AND count(*) <={$params['backup_num_max']}";

            } elseif (!empty($params['backup_num_min'])&&empty($params['backup_num_max'])) {
                $backup_num_ext =  " count(*) >= {$params['backup_num_min']} ";

            } else {
                $backup_num_ext =  " count(*) <= {$params['backup_num_max']} ";

            }

            $query_builder->where(" A.supplier_code IN (
                      SELECT supplier_code FROM (SELECT  supplier_code,sku  FROM pur_alternative_suppliers  GROUP BY supplier_code,sku) AS w GROUP BY supplier_code
                      HAVING ".$backup_num_ext.") "
            );
        }

        //是否包邮
        if (!empty($params['is_postage'])) {
            $query_builder->where('A.is_postage',$params['is_postage']);

        }

        if (!empty($params['tapDateStr_diff'])) {//账期是否修改
            if($params['tapDateStr_diff'] == 1){
                $query_builder->where('D.supplier_settlement', SUPPLIER_SETTLEMENT_CODE_TAP_DATE);
                $query_builder->where('A.tap_date_str=A.tap_date_str_sync');
            }elseif($params['tapDateStr_diff'] == 2){
                $query_builder->where('D.supplier_settlement', SUPPLIER_SETTLEMENT_CODE_TAP_DATE);
                $query_builder->where('A.tap_date_str<>A.tap_date_str_sync');
            }else{
                $query_builder->where('A.supplier_code NOT IN(
                    SELECT supplier_code 
                    FROM pur_supplier_payment_info 
                    WHERE is_del=0 
                    AND supplier_settlement='.SUPPLIER_SETTLEMENT_CODE_TAP_DATE.')'
                );
            }
        }

        $numrows = $query_builder->get()->row_array();
//        echo $numrows['numrows'];exit;
        $count = $numrows['numrows'];
//        $count = $query_builder->count_all_results();
        return array(
            'count' => $count,
            'page_count' => ceil($count / $pageSize),
        );
    }
    /**
     * @desc 获取供应商列表(分页)
     * @return array()
     **@author Jackson
     * @Date 2019-01-21 17:01:00
     */
    public function get_by_page($params = [])
    {

        $supplier_level_list = getSupplierLevel();
        $develop_source_arr = [1=>'门户系统',2=>'产品系统'];

        $platform_source_arr = [1=>'线下',2=>'线上阿里店铺',3=>'线上淘宝',4=>'线上拼多多',5=>'线上京东',6=>'其他线上平台'];

        $business_line_arr =[1=>'国内',2=>'海外',3=>'国内/海外'];

        $cooperation_status = getCooperationStatus();



        $params = $this->table_query_filter($params);

        if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
            // 当查找一个供应商时 带出其关联的供应商，通过code查询
            if (is_array($params['supplier_code'])) {
                $query_supplier_codes = $params['supplier_code'];
            } else {
                $query_supplier_codes = [$params['supplier_code']];
            }
            // strict=1为严格模式，表示不查关联供应商
            if(!isset($params['strict']) or $params['strict'] != 1){
                $relation_supplier = $this->get_relation_supplier_detail(current($params['supplier_code']),false);
                if($relation_supplier) $query_supplier_codes = array_merge($query_supplier_codes,array_keys($relation_supplier));
            }
        }

        $query_builder = $this->purchase_db;

        //查询字段
        $fields = 'A.is_push_purchase,A.is_gateway,A.id,A.supplier_code,A.supplier_name,A.supplier_settlement,'
            .'A.payment_method,A.supplier_level,A.supplier_source,A.create_user_name,A.sku_num,A.store_link,'
            .'A.status,A.audit_status,A.search_status,A.is_complete,A.is_cross_border,A.is_diversion_status,'
            .'A.business_line,A.develop_source,A.platform_source,A.restart_date,A.sku_sale_num,A.sku_no_sale_num,'
            .'A.sku_other_num,A.disabled_times,A.supplier_grade,A.create_time,A.order_num,A.sku_shipping_num,'
            .'A.register_address,A.register_date,A.is_postage,A.ship_province,A.ship_city,A.ship_area,'
            .'A.ship_address,C.linelist_cn_name,A.tap_date_str,A.tap_date_str_sync';
        $query_builder->select($fields)
            ->from($this->table_name . ' AS A')
            ->join($this->supplier_product_line_table_name . ' AS B', "A.supplier_code=B.supplier_code AND B.status=1", 'LEFT')
            ->join($this->product_line_table_name . " AS C", "B.first_product_line=C.product_line_id", 'LEFT')
            ->join("pur_supplier_payment_info AS D", "A.supplier_code=D.supplier_code AND D.is_del=0", 'LEFT')
            ->join($this->pur_supplier_cooperation_amount . " AS E", "A.supplier_code=E.supplier_code", 'LEFT');

        if (isset($query_supplier_codes) && !empty($query_supplier_codes)) {
            $query_builder->where_in('A.supplier_code', $query_supplier_codes);
        }
        if (isset($params['payment_method'])) {
            $query_builder->where('D.payment_method', $params['payment_method']);
        }
        $searchSupplierCode = [];
        if( isset($params['overseas_supplier_code']) ){

            $searchSupplierCode = array_merge($searchSupplierCode,$params['overseas_supplier_code']);

        }

        if( isset($params['domestic_supplier_code']) ){

            $searchSupplierCode = array_merge($searchSupplierCode,$params['domestic_supplier_code']);
        }
        if(!empty($searchSupplierCode)){

            if(count($searchSupplierCode)>2000){

                $domestic_supplier_code = array_chunk($searchSupplierCode,2000);
                $query_builder->where("( 1=1 ")->where_in('A.supplier_code',$domestic_supplier_code[0]);
                foreach($domestic_supplier_code as $domestic_supplier_code_value){

                    $query_builder->or_where_in('A.supplier_code',$domestic_supplier_code_value);
                }
                $query_builder->where(" 1=1)");
            }else{
                $query_builder->where_in('A.supplier_code', $searchSupplierCode);
            }
        }




        if( isset($params['reportloss_start']) && isset($params['reportloss_end'])){

            $query_builder->where(" A.supplier_code IN (
                        SELECT
                            supplier_code
                        FROM
                            pur_purchase_order AS orders
                        LEFT JOIN `pur_purchase_order_reportloss` AS `reportloss` ON `reportloss`.`pur_number` = `orders`.`purchase_number`
                        WHERE reportloss.status=4
                        GROUP BY
                            orders.supplier_code
                        HAVING
                            SUM(loss_totalprice) >= {$params['reportloss_start']}  AND SUM(loss_totalprice)<={$params['reportloss_end']}
                    )"
            );
        }



        if (isset($params['buyer'])) {
            if ($params['buyer']==0) {
                $query_builder->where("A.supplier_code NOT IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_type=1 GROUP BY supplier_code)");

            } else {
                $buyer_str = implode(',',$params['buyer']);
                $query_builder->where("A.supplier_code IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_id in ({$buyer_str}) AND pur_{$this->buyer_name}.status='1' and buyer_type in (1,2,3)   GROUP BY supplier_code)");

            }

        }

        if(isset($params['is_gateway']) && $params['is_gateway'] != NULL){

            if( $params['is_gateway'] == 1){
                $query_builder->where_in('A.is_gateway',SUGGEST_IS_GATEWAY_YES)->where('A.is_push_purchase',1);
            }else{
                $query_builder->where('(A.is_gateway',SUGGEST_IS_GATEWAY_NO)->or_where('A.is_push_purchase=2)');
            }
        }
        if (isset($params['create_user_id'])&&!empty($params['create_user_id'])) {
            $query_builder->where_in('A.create_user_id', $params['create_user_id']);
        }
        if (isset($params['supplier_settlement'])) {

            if(is_array($params['supplier_settlement'])) {
                $query_builder->where_in('D.supplier_settlement', $params['supplier_settlement']);
            }else {
                $query_builder->where('D.supplier_settlement', $params['supplier_settlement']);
            }
        }
        if (isset($params['supplier_level'])&&!empty($params['supplier_level'])) {
            $query_builder->where_in('A.supplier_level', $params['supplier_level']);
        }
        if (isset($params['is_cross_border'])) {
            $query_builder->where('A.is_cross_border', $params['is_cross_border']);
        }
        if (isset($params['status'])) {
            //$query_builder->where('A.status',$params['status']);
            if(is_array($params['status'])){
                $query_builder->where_in('A.audit_status', $params['status']);
            }else{
                $query_builder->where('A.audit_status', $params['status']);
            }
        }
        if (isset($params['first_product_line'])) {
            $query_builder->where_in('B.first_product_line', $params['first_product_line']);
        }

        if (isset($params['cooperation_status'])) {
            $query_builder->where('A.status', $params['cooperation_status']);
        }

        if (isset($params['supplier_source'])) {
            $query_builder->where('A.supplier_source', $params['supplier_source']);
        }


        if (isset($params['is_diversion_status'])) {
            $query_builder->where('A.is_diversion_status', $params['is_diversion_status']);
        }

        if (isset($params['is_complete']) && is_numeric($params['is_complete'])) {
            $query_builder->where('A.is_complete', $params['is_complete']);
        }
        //有效sku数量
        if (isset($params['min_sku_num'])) {
            $query_builder->where('A.sku_num >=', intval($params['min_sku_num']));
        }
        if (isset($params['max_sku_num'])) {
            $query_builder->where('A.sku_num <=', intval($params['max_sku_num']));
        }

        if (!empty($params['cooperation_letter'])) {
            $query_builder->join("pur_supplier_images AS images", "A.supplier_code=images.supplier_code AND images.image_type='cooperation_letter'", 'LEFT');

            if ($params['cooperation_letter'] == 1) {
                $query_builder->where('images.image_url IS NOT NULL');

            } else {
                $query_builder->where('images.image_url IS  NULL');

            }


        }
        //关联供应商
        if (!empty($params['is_relation'])) {

            $query_builder->join("pur_relation_supplier AS relation", "A.supplier_code=relation.supplier_code", 'LEFT');



            if ($params['is_relation'] == 1) {
                $query_builder->where('relation.supplier_code is not null');

            } else {
                $query_builder->where("relation.supplier_code  is null");



            }

            
        }

        //备用下单次数
        if (!empty($params['min_order_num'])) {
            $query_builder->where('A.order_num >=', intval($params['min_order_num']));


        }


        if (!empty($params['max_order_num'])) {
            $query_builder->where('A.order_num <=', intval($params['max_order_num']));


        }



        if (isset($params['check'])&&!empty($params['check'])) {






            $query_builder->where("A.supplier_code IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_id = {$params['check']} AND pur_{$this->buyer_name}.status='1' and buyer_type = '10'   GROUP BY supplier_code)");




        }

        //拜访次数查询


        if( !empty($params['visit_times_min']) || !empty($params['visit_times_max'])){
            if (!empty($params['visit_times_min'])&&!empty($params['visit_times_max'])) {
               $visit_num_ext =  " count(*) >= {$params['visit_times_min']}  AND count(*) <={$params['visit_times_max']}";

            } elseif (!empty($params['visit_times_min'])&&empty($params['visit_times_max'])) {
                $visit_num_ext =  " count(*) >= {$params['visit_times_min']} ";

            } else {
               $visit_num_ext =  " count(*) <= {$params['visit_times_max']} ";

            }

            $query_builder->where(" A.supplier_code IN (
                      SELECT supplier_code FROM pur_supplier_visit_apply GROUP BY supplier_code
                      HAVING ".$visit_num_ext.") "
            );
        }

         //备用次数查询
        if( !empty($params['backup_num_min']) || !empty($params['backup_num_max'])){
            if (!empty($params['backup_num_min'])&&!empty($params['backup_num_max'])) {
                $backup_num_ext =  " count(*) >= {$params['backup_num_min']}  AND count(*) <={$params['backup_num_max']}";

            } elseif (!empty($params['backup_num_min'])&&empty($params['backup_num_max'])) {
                $backup_num_ext =  " count(*) >= {$params['backup_num_min']} ";

            } else {
                $backup_num_ext =  " count(*) <= {$params['backup_num_max']} ";

            }

            $query_builder->where(" A.supplier_code IN (
                      SELECT supplier_code FROM (SELECT  supplier_code,sku  FROM pur_alternative_suppliers  GROUP BY supplier_code,sku) AS w GROUP BY supplier_code
                      HAVING ".$backup_num_ext.") "
            );
        }

        //是否包邮
        if (!empty($params['is_postage'])) {
            $query_builder->where('A.is_postage',$params['is_postage']);

        }

        if (!empty($params['tapDateStr_diff'])) {//账期是否修改
            if($params['tapDateStr_diff'] == 1){
                $query_builder->where('D.supplier_settlement', SUPPLIER_SETTLEMENT_CODE_TAP_DATE);
                $query_builder->where('A.tap_date_str=A.tap_date_str_sync');
            }elseif($params['tapDateStr_diff'] == 2){
                $query_builder->where('D.supplier_settlement', SUPPLIER_SETTLEMENT_CODE_TAP_DATE);
                $query_builder->where('A.tap_date_str<>A.tap_date_str_sync');
            }else{
                $query_builder->where('A.supplier_code NOT IN(
                    SELECT supplier_code 
                    FROM pur_supplier_payment_info 
                    WHERE is_del=0 
                    AND supplier_settlement='.SUPPLIER_SETTLEMENT_CODE_TAP_DATE.')'
                );
            }
        }


        // 3.分页参数
        $pageSize = query_limit_range(isset($params['limit']) ? $params['limit'] : 0);
        $page = !isset($params['offset']) || intval($params['offset']) <= 0 ? 1 : intval($params['offset']);
        $offset = ($page - 1) * $pageSize;
        // 三个月合作金额搜索条件
        if ((isset($params['min_nineth_data']) && strlen($params['min_nineth_data']) > 0) || (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0)) {
            $params['min_nineth_data'] = $params['min_nineth_data'] ?? 0;
            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
            $end = date('Y-m-d H:i:s');
            $nineth_sql = "A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING (sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) >= {$params['min_nineth_data']})";
//            $query_builder->where(" D.calculate_date >= '{$start}' AND D.calculate_date  <= '{$end}' GROUP BY D.supplier_code HAVING sum(D.cooperation_amount) >= {$params['min_nineth_data']}");
        }
        if (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0) {
//            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
//            $end = date('Y-m-d H:i:s');
//            $query_builder->where("A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}");
            $nineth_sql .= " AND sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}";
        }
        if (isset($nineth_sql)) {
            $query_builder->where($nineth_sql . ')');
        }

        if (!empty($params['develop_source'])) {
            $query_builder->where('A.develop_source',$params['develop_source']);

        }
        if (!empty($params['platform_source'])) {
            $query_builder->where('A.platform_source',$params['platform_source']);

        }

        if (!empty($params['business_line'])) {
            $query_builder->where('A.business_line',$params['business_line']);

        }


        if (!empty($params['create_time_start'])) {
            $query_builder->where('A.create_time >=', $params['create_time_start']);


        }

        if (!empty($params['create_time_end'])) {
            $query_builder->where('A.create_time <=', $params['create_time_end']);


        }


        if (!empty($params['restart_date_start'])) {
            $query_builder->where('A.restart_date >=', $params['restart_date_start']);


        }

        if (!empty($params['restart_date_end'])) {
            $query_builder->where('A.restart_date <=', $params['restart_date_end']);


        }



        $list = $query_builder->order_by('A.id DESC')->group_by('A.id')->offset($offset)->limit($pageSize)->get()->result_array();







        if ($list) {
            $supplier_codes = array_column($list, 'supplier_code');
            $visit_info     = $this->get_visit_times($supplier_codes);
            $backup_info    = [];
            // 获取采购员信息
            $buyer_lists = $this->purchase_db->select("supplier_code,buyer_type,buyer_name as buyer")
                ->from($this->buyer_name)
                ->where('status', 1)
                ->where_in('supplier_code', $supplier_codes)
                ->order_by('buyer_type asc')
                ->get()
                ->result_array();
            $buyer_lists = arrayKeyToColumnMulti($buyer_lists, 'supplier_code');

            if (!empty($supplier_codes)) {
                $supplier_str = '';
                foreach ($supplier_codes as $code) {
                    $supplier_str .= "'".$code."',";

                }
                $supplier_str = trim($supplier_str,',');
                $back_sql       = "SELECT COUNT(*) as num,supplier_code FROM (SELECT  supplier_code,sku  FROM pur_alternative_suppliers where supplier_code in ({$supplier_str})  GROUP BY supplier_code,sku) AS s GROUP BY supplier_code
 ";
                $back_result  = $this->purchase_db->query($back_sql)->result_array();
                $backup_info  = !empty($back_result)?array_column($back_result,'num','supplier_code'):[];

            }






            // 获取支付方式, 结算方式
            $payment_info = $this->purchase_db->select("supplier_code,group_concat(payment_method) as payment_method,group_concat(supplier_settlement) as supplier_settlement")
                ->from('pur_supplier_payment_info')
                ->where_in('supplier_code', $supplier_codes)
                ->where('is_del', 0)
                ->group_by('supplier_code')
                ->get()
                ->result_array();
            $payment_info = array_column($payment_info, NULL, 'supplier_code');
            foreach ($payment_info as $key => &$item){
                if (!empty($item['payment_method'])){
                    $payment_method_list = explode(',',$item['payment_method']);
                    $payment_method_list = array_unique($payment_method_list);
                    $item['payment_method'] = implode(',',$payment_method_list);
                }
                if (!empty($item['supplier_settlement'])){
                    $supplier_settlement_list = explode(',',$item['supplier_settlement']);
                    $supplier_settlement_list = array_unique($supplier_settlement_list);
                    $item['supplier_settlement'] = implode(',',$supplier_settlement_list);
                }
            }

            //获取最新一条审核备注
//            $querys_string = format_query_string($supplier_codes);


//            $sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log AS a
//	JOIN ( SELECT MAX( id ) AS mid FROM pur_operator_log WHERE record_number IN ({$querys_string}) AND record_type
//	= 'SUPPLIER_UPDATE_LOG' AND operate_route = 'supplier/Supplier/supplier_review' GROUP BY record_number ) AS b ON a.id = b.mid";
//
//            $detail_list = $this->purchase_db->query($sql)->result_array();
//            $detail_list = array_column($detail_list, 'content_detail', 'supplier_code');


//查询禁用备注
//            $query_sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log AS a
//	JOIN ( SELECT MAX( id ) AS mid FROM pur_operator_log WHERE record_number IN ({$querys_string}) AND record_type
//	in ('SUPPLIER_UPDATE_LOG','PUR_SUPPLIER') AND operate_route = 'supplier/Supplier/supplier_disable' AND content = '供应商禁用成功' GROUP BY record_number ) AS b ON a.id = b.mid";;
//
//            $fobidden_remark = $this->purchase_db->query($query_sql)->result_array();
//            $fobidden_remark_list =  array_column($fobidden_remark,'content_detail','supplier_code');


//            //更新或启用新增申请备注
//            $new_query_sql = "SELECT `ext`,`record_number` AS `supplier_code`,id FROM pur_operator_log AS a
//	JOIN ( SELECT MAX( id ) AS mid FROM pur_operator_log WHERE record_number IN ({$querys_string}) AND record_type
//	= 'SUPPLIER_UPDATE_LOG' GROUP BY record_number ) AS b ON a.id = b.mid";;
//            $apply_remark = $this->purchase_db->query($new_query_sql)->result_array();
//            $apply_remark_list =  array_column($apply_remark,'ext','supplier_code');
            //三个月合作金额
            $cooperation_amount = $this->get_supplier_cooperation_amount($supplier_codes);

            //上个月合作金额

            $before_month_start = DATE('Y-m-01',strtotime("-1month"));
            $before_month_end   = DATE('Y-m-t',strtotime("-1month"));

            $cooperation_amount_one_month = $this->get_supplier_cooperation_amount($supplier_codes,$before_month_start,$before_month_end);

            $supplier_reportData = [];
            if( !empty($list) ){

                $supplier_codes = array_filter(array_column( $list,"supplier_code"));

                $supplier_reportData_query = $this->purchase_db->select(" SUM(loss_totalprice) AS loss_totalprice,orders.supplier_code")->from("purchase_order AS orders")->join(" pur_purchase_order_reportloss AS reportloss","reportloss.pur_number = orders.purchase_number");
                $supplier_reportData = $supplier_reportData_query->where("reportloss.status",4)->where_in("orders.supplier_code",$supplier_codes)->group_by("orders.supplier_code")->get()->result_array();
                if( !empty($supplier_reportData)){
                    $supplier_reportData = array_column( $supplier_reportData,NULL,"supplier_code");
                }


            }
            foreach ($list as $key => $value) {
                $list[$key]['business_line'] = $business_line_arr[$value['business_line']]??'';
                $list[$key]['develop_source'] = $develop_source_arr[$value['develop_source']]??'';
                $list[$key]['platform_source'] = $platform_source_arr[$value['platform_source']]??'';
                $list[$key]['cooperation_status'] = $cooperation_status[$value['status']]??'';

                $now_buyer = isset($buyer_lists[$value['supplier_code']]) ? $buyer_lists[$value['supplier_code']] : [];
                $list[$key]['buyer'] = array_column($now_buyer, 'buyer', 'buyer_type');

                if(  isset($supplier_reportData[$value['supplier_code']]) && $supplier_reportData[$value['supplier_code']]['loss_totalprice'] != NULL ){

                    $list[$key]['loss_money'] = $supplier_reportData[$value['supplier_code']]['loss_totalprice'];
                }else{

                    $list[$key]['loss_money'] = 0;
                }
                //$list[$key]['supplier_level']= !empty($supplier_level_list[$list[$key]['supplier_level']])?$supplier_level_list[$list[$key]['supplier_level']]:$list[$key]['supplier_level'];

				 if(!empty($list[$key]['supplier_level'])) {
                    if (!empty($supplier_level_list[$list[$key]['supplier_level']])) {
                        $list[$key]['supplier_level'] = $supplier_level_list[$list[$key]['supplier_level']];
                    }else{
                        $list[$key]['supplier_level'];
                    }
                }else{
                    $list[$key]['supplier_level'] = '';
                }


                $list[$key]['payment_method'] = isset($payment_info[$value['supplier_code']]) ? $payment_info[$value['supplier_code']]['payment_method'] : '';
                $list[$key]['supplier_settlement'] = isset($payment_info[$value['supplier_code']]) ? $payment_info[$value['supplier_code']]['supplier_settlement'] : '';

                if($value['tap_date_str'] == $value['tap_date_str_sync']){
                    $list[$key]['tap_date_str_change'] = '未修改';
                }else{
                    $list[$key]['tap_date_str_change'] = '已修改';
                }
                if(empty($list[$key]['supplier_settlement']) or !in_array(SUPPLIER_SETTLEMENT_CODE_TAP_DATE,explode(',',$list[$key]['supplier_settlement'])) ){
                    $list[$key]['tap_date_str'] = '-';
                    $list[$key]['tap_date_str_sync'] = '-';
                }
                //获取最新一条审核备注
                $sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log AS a
	WHERE record_number = '{$value['supplier_code']}' AND record_type
	= 'SUPPLIER_UPDATE_LOG' AND operate_route = 'supplier/Supplier/supplier_review' order by id desc limit 1";

                $detail_list = $this->purchase_db->query($sql)->row_array();
//                $detail_list = array_column($detail_list, 'content_detail', 'supplier_code');
                $detail_list[$value['supplier_code']] = $detail_list['content_detail'];

                $list[$key]['contract_notice'] = isset($detail_list[$value['supplier_code']]) ? $detail_list[$value['supplier_code']] : '';

                if( $value['is_gateway'] == SUGGEST_IS_GATEWAY_YES && $value['is_push_purchase'] == 1){

                    $list[$key]['is_gateway_ch'] = '是';
                }else{
                    $list[$key]['is_gateway_ch'] = '否';
                }

                if ($value['status'] == IS_DISABLE) {//如果供应商处于禁用状态 显示禁用备注

                    $query_sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log WHERE record_number = '{$value['supplier_code']}' AND record_type
	in ('SUPPLIER_UPDATE_LOG','PUR_SUPPLIER') AND operate_route = 'supplier/Supplier/supplier_disable' AND content = '供应商禁用成功' order by id desc limit 1";
                    $fobidden_remark = $this->purchase_db->query($query_sql)->row_array();
//                    $fobidden_remark_list = array_column($fobidden_remark, 'content_detail', 'supplier_code');
                    $fobidden_remark_list[$value['supplier_code']] = $fobidden_remark['content_detail'];

                    $current_forbidden_remark = isset($fobidden_remark_list[$value['supplier_code']]) ? mb_substr($fobidden_remark_list[$value['supplier_code']], 4) : '';
                    $current_forbidden_remark ? $list[$key]['contract_notice'] .= PHP_EOL . '禁用原因:' . $current_forbidden_remark : '';
                }elseif($value['status'] == PRE_DISABLE){




                    $query_sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log WHERE record_number = '{$value['supplier_code']}' AND record_type
	in ('SUPPLIER_UPDATE_LOG','PUR_SUPPLIER') AND operate_route = 'supplier/supplier/pre_disable' AND content = '供应商预禁用成功' order by id desc limit 1";
                    $fobidden_remark = $this->purchase_db->query($query_sql)->row_array();
//                    $fobidden_remark_list = array_column($fobidden_remark, 'content_detail', 'supplier_code');
                    $fobidden_remark_list[$value['supplier_code']] = $fobidden_remark['content_detail'];

                    $current_forbidden_remark = isset($fobidden_remark_list[$value['supplier_code']]) ? mb_substr($fobidden_remark_list[$value['supplier_code']], 5) : '';
                    $current_forbidden_remark ? $list[$key]['contract_notice'] .= PHP_EOL . '预禁用原因:' . $current_forbidden_remark : '';

                } else {
                    //更新或启用新增申请备注

                    $new_query_sql = "SELECT `ext`,`record_number` AS `supplier_code`,id FROM pur_operator_log WHERE record_number = '{$value['supplier_code']}' AND record_type 
	= 'SUPPLIER_UPDATE_LOG' order by id desc limit 1";

                    $apply_remark = $this->purchase_db->query($new_query_sql)->row_array();
                    $apply_remark_list[$value['supplier_code']] = $apply_remark['ext'];
                    if (empty($apply_remark_list[$value['supplier_code']])) {

                    }
                }

                $list[$key]['supplier_source_name'] = getSupplierSource($value['supplier_source']);
                if (isset($apply_remark_list[$value['supplier_code']]) && strpos($apply_remark_list[$value['supplier_code']], '申请备注')) {
                    $list[$key]['contract_notice'] = $apply_remark_list[$value['supplier_code']];
                }

//                $settlement = $this->get_settlement_name($value['supplier_code']);
//                if (!empty($settlement)) {
//                    $settlement_name = implode(',', array_column($settlement, 'settlement_name'));
//                    $list[$key]['supplier_settlement'] = $settlement_name;
//                } else {
//                    $list[$key]['supplier_settlement'] = '';
//                }
                //三月合作金额判断
                $list[$key]['cooperation_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['cooperation_amount'] : 0;
                $list[$key]['payment_days_online_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['payment_days_online_amount'] : 0;
                $list[$key]['payment_days_offline_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['payment_days_offline_amount'] : 0;
                $list[$key]['no_payment_days_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['no_payment_days_amount'] : 0;


                $list[$key]['cooperation_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['cooperation_amount'] : 0;
                $list[$key]['payment_days_online_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['payment_days_online_amount'] : 0;
                $list[$key]['payment_days_offline_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['payment_days_offline_amount'] : 0;
                $list[$key]['no_payment_days_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['no_payment_days_amount'] : 0;




//                if(isset($cooperation_amount[$value['supplier_code']])){
//                if (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0 && $list[$key]['cooperation_amount'] > $params['max_nineth_data']) {
//                    unset($list[$key]);
//                }
//                if (isset($params['min_nineth_data']) && strlen($params['min_nineth_data']) > 0 && $list[$key]['cooperation_amount'] < $params['min_nineth_data']) {
//                    if (isset($list[$key]))
//                        unset($list[$key]);
//                }
//                }
                //关联供应商显示
                $relation_info_show = [];
                $relation_info = $this->get_relation_supplier_detail($value['supplier_code'],false);
                if (!empty($relation_info)) {
                    foreach ($relation_info as $re_info) {
                        $relation_info_show[] = $re_info;

                    }

                }


                $list[$key]['relation_info'] = $relation_info_show;
                $list[$key]['visit_times']              =     $visit_info[$value['supplier_code']]??[];
                $list[$key]['backup_number']            =     $backup_info[$value['supplier_code']]??0;
                $list[$key]['is_postage']               =     getIsPostage($value['is_postage']);




                if (!empty($value['ship_province'])) {
                     $list[$key]['ship_province'] = $this->addressModel->get_address_name_by_id($value['ship_province']);


                }

                if (!empty($value['ship_city'])) {
                    $list[$key]['ship_city'] = $this->addressModel->get_address_name_by_id($value['ship_city']);



                }

                if (!empty($value['ship_area'])) {
                    $list[$key]['ship_area'] = $this->addressModel->get_address_name_by_id($value['ship_area']);

                }










            }
        }

        return array(
            'list' => $list,
        );

    }

    /**
     * @desc 获取下拉供应商列表
     * @return array()
     **@author Jackson
     * @Date 2019-01-21 17:01:00
     */
    public function get_by_supplier_list($params)
    {
        //搜索条件
        $condition = [];
        $likeField = 'supplier_name';
        $limit = 40;
        if (isset($params[$likeField]) && !empty($params[$likeField])) {
            $condition["$likeField LIKE"] = $params[$likeField] . '%';
            $limit = 300;
        }
        //排序
        $orderBy = '';

        //group by
        $groupBy = 'supplier_code';
        //查询字段
        $fields = 'supplier_code,supplier_name,status';
        $result = $this->getDataList($condition, $fields, $orderBy, 0, $limit, $groupBy);

        return $result['data'];
    }

    /**
     * @desc 获取供应商详情信息
     * @return array()
     **@author Jackson
     * @Date 2019-01-21 17:01:00
     */
    public function get_details($id = 0)
    {
        //查询字段
        $fields =  'a.id,a.credit_code,a.supplier_name,a.supplier_code,a.register_address,a.register_date,a.supplier_level,
            a.supplier_type, a.supplier_source, a.first_cooperation_time, a.store_link, a.create_user_name,a.create_time,
            a.province, a.city, a.area , a.complete_address, a.ship_province, a.ship_city, a.ship_area, a.ship_address, a.is_cross_border,
            a.shop_id, a.tap_date_str, a.quota, a.surplus_quota, a.shipping_method_id, a.cooperation_type, a.payment_cycle, a.transport_party,
            a.product_handling, a.commission_ratio, a.purchase_amount,a.main_category, a.taxrate,
            a.invoice, a.invoice_tax_rate, a.business_scope, a.purchase_time, a.cooperation_price, a.settlement_type, a.supplier_settlement, a.is_complete,
            a.legal_person, a.is_agent, a.is_postage,a.audit_status,a.platform_source,a.reconciliation_agent,a.agent_mobile,
            b.category_name as prod_first_product_line,
            c.id as prod_supplier_id,c.category_num, c.one_mouth_num_type, c.six_mouth_num_type, c.supplier_level as prod_supplier_level, c.key_point_reason, c.department_type, c.category_type,
            d.first_product_line,d.second_product_line,a.tap_date_str_sync';
        //数据查询
        $result = $this->purchase_db->select($fields)
            ->from($this->table_name.' a')
            ->join($this->pur_prod_supplier_ext.' c', 'a.supplier_code = c.supplier_code and c.is_del=0','left')
            ->join($this->pur_prod_supplier_category.' b', 'c.id = b.supplier_id and b.is_del=0','left')
            ->join($this->supplier_product_line_table_name.' d', 'a.supplier_code = d.supplier_code and d.status=1','left')
            ->where('a.id',$id)
            ->get()->result_array();

        return array(
            'list' => $result,
        );
    }

    /**
     * 供应商 启用 或 禁用
     * @param int $id 供应商ID
     * @param int $status 启用为1：（1.启用,2.禁用）
     * @param string $remark 备注
     * @return array
     */
    public function supplier_disable($id, $status, $remark, $send = true)
    {
        $this->load->helper('api');
        $return = ['code' => false, 'message' => ''];
        $supplierInfo = $this->get_supplier_by_id($id, false);
        if (empty($supplierInfo)) {
            return ['code' => false, 'message' => '供应商状态不存在'];
        }
        $product_url = getConfigItemByName('api_config', 'product_system', 'updateSupplierStatus'); //获取推送供应商url
        $access_taken = getOASystemAccessToken();
        if (empty($product_url)) {
            return ['code' => false, 'message' => '产品系统api不存在'];
        }
        if (empty($access_taken)) {
            return ['code' => false, 'message' => '获取access_token值失败'];
        }

        if ($send) {
            //同步到产品系统
            $send_result = $this->send_supplier_status($supplierInfo['supplier_code'], $status, $remark);
        }

        $operator_id = getActiveUserId();
        $this->load->model('user/purchase_user_model');
        $user_info = $this->purchase_user_model->get_user_info_by_id($operator_id);
        $data_post = [
            'supplierCode' => $supplierInfo['supplier_code'],
            'status' => $status == IS_DISABLE ? 7 : 3, //供应商状态 3已审核（启用） 4被驳回 7禁用 (对应产品系统)
            'createTime' => date('Y-m-d H:i:s'),
            'createUser' => isset($user_info['staff_code']) ? $user_info['staff_code'] : getActiveUserName(),
            'modifyReason' => $remark,
        ];
        $url_api = $product_url . "?access_token=" . $access_taken;
        $results = getCurlData($url_api, json_encode($data_post), 'post', array('Content-Type: application/json'));
        $product_result = json_decode($results, true);

        if ($status == IS_DISABLE) {// 供应商禁用
            /*if(!in_array($supplierInfo['audit_status'],[SUPPLIER_PURCHASE_REJECT,SUPPLIER_SUPPLIER_REJECT,SUPPLIER_FINANCE_REJECT,SUPPLIER_REVIEW_PASSED])){
                $return['message'] = '供应链驳回|采购驳回|财务驳回 状态下才能禁用';
            }*/
            if (empty($return['message'])) {
                list($_result_status, $_message) = $this->update_supplier(['status' => 2, 'search_status' => IS_DISABLE, 'audit_status' => '7','disabled_times'=>$supplierInfo['disabled_times']+1], $id);
                if ($_result_status) {
                    operatorLogInsert(
                        [
                            'id' => $supplierInfo['supplier_code'],
                            'type' => 'supplier_update_log',
                            'content' => '供应商禁用成功',
                            'detail' => '[禁用]' . $remark,
                            'ext' => $supplierInfo['supplier_code'],
                            'operate_type' => SUPPLIER_NORMAL_DISABLE,
                        ]);
                    rejectNoteInsert(
                        [
                            'reject_type_id' => 4,
                            'link_id' => $id,
                            'link_code' => $supplierInfo['supplier_code'],
                            'reject_remark' => '[禁用]' . $remark
                        ]);

                    //供应商禁用需终结之前生成的审核记录 
                    $this->supplier_disable_record($supplierInfo['supplier_code'], $remark);

                    $return['code'] = true;
                    $return['message'] = '供应商禁用成功';
                } else {
                    $return['message'] = '供应商禁用失败';
                }

                $api_log = [
                    'record_number' => $supplierInfo['supplier_code'],
                    'api_url' => $url_api,
                    'record_type' => '推送供应商状态产品系统',
                    'post_content' => json_encode($data_post),
                    'response_content' => $results,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                $this->purchase_db->insert('api_request_log', $api_log);
                //$return['message'] = $product_result['msg'];
            }
        } else {// 供应商启用
            list($_result_status, $_message) = $this->update_supplier(['status' => 1, 'search_status' => IS_ENABLE], $id);
            if ($_result_status) {
                operatorLogInsert(
                    [
                        'id' => $supplierInfo['supplier_code'],
                        'type' => 'pur_' . $this->table_name,
                        'content' => '供应商启用成功',
                        'detail' => '启用后的供应商需重走（需经财务审核的）更新流程-状态变更为【' . getSupplierAuditResultStatus(SUPPLIER_WAITING_PURCHASE_REVIEW) . '】' . $remark,
                        'ext' => $supplierInfo['supplier_code'],
                    ]);

                rejectNoteInsert(
                    [
                        'reject_type_id' => 4,
                        'link_id' => $id,
                        'link_code' => $supplierInfo['supplier_code'],
                        'reject_remark' => '[启用]' . $remark
                    ]);
                $return['code'] = true;
                $return['message'] = '供应商启用成功-启用后的供应商需重走（需经财务审核的）更新流程';
            } else {
                $return['message'] = '供应商启用失败';
            }

        }

        return $return;

    }

    /**
     * 新增供应商
     * @param $params
     * @return array
     * @author Jolon
     */
    public function insert_supplier($params)
    {
        // 过滤非数据库字段
        $params = $this->filterNotExistFields($params);
        $result = $this->insert($params);
        if ($result) {
            return array(true, "新增成功");
        } else {
            return array(false, "新增失败：" . $this->getWriteDBError());
        }
    }

    /**
     * @desc 更新数据
     * @return array()
     **@author Jackson
     * @Date 2019-01-22 17:01:00
     */
    public function update_supplier(array $parames, $id = 0, $revies = false)
    {
        //更新条件
        $condition = [];
        $_msg = '';
        if (!empty($parames)) {

            //增加用户信息
            $parames['modify_user_id'] = '';//用户ID
            $parames['modify_user_name'] = '';//用户名
            analyzeUserInfo($parames);

            $parames['modify_time'] = date("Y-m-d H:i:s");
            //审核
            if ($revies) {

                $supplierCode = explode(',', $parames['supplier_code']);
                $condition['where_in'] = array('supplier_code' => $supplierCode);
                $_msg = '审核成功!';
                unset($parames['supplier_code']);

            } else {
                //更新数据
                $condition['id'] = $id ? $id : $parames['id'];
                $_msg = '更新成功!';
            }
            //获取更新前的数据
            $updateBefore = $this->getDataByCondition($condition);

            // 过滤非数据库字段
            $parames = $this->filterNotExistFields($parames);
            $result = $this->update($parames, $condition);

            //记录操作日志
            if ($result) {

                $ids = isset($condition['id']) ? [$condition['id']] : 0;
                //批量审核,需查出所有被更新ID根据supplier_code
                if ($revies) {
                    $ids = array_column($updateBefore, 'id');
                }

                if (is_array($ids)) {

                    //删除不必要字段
                    foreach ($parames as $key => $val) {
                        if (in_array($key, array('modify_user_id', 'modify_user_name', 'modify_time', 'id'))) {
                            unset($parames[$key]);
                        }
                    }

                    //解析更新前后数据
                    $changDatas = $this->checkChangData($updateBefore, $parames, array('first_cooperation_time', 'modify_time'));
                    if (!empty($changDatas)) {
                        foreach ($ids as $key => $_id) {
                            if (!isset($changDatas[$_id])) continue;
                            $supplier_info = $this->get_supplier_by_id($_id);

                            operatorLogInsert(
                                [
                                    'id' => $_id,
                                    'type' => 'pur_' . $this->table_name,
                                    'content' => $revies ? '供应商数据审核' : '供应商基础数据更新',
                                    'detail' => $changDatas[$_id],
                                    'ext' => $supplier_info['supplier_code']
                                ]);

                            tableChangeLogInsert(
                                [
                                    'record_number' => $_id,
                                    'table_name' => 'pur_' . $this->table_name,
                                    'change_type' => 2,
                                    'change_content' => $changDatas[$_id],
                                ]);
                        }
                    }
                }

            }

            //返回结果
            if ($this->getAffectedRows() > 0) {
                return array(true, $_msg);
            }
            if ($result) {
                return array(true, "no");
            } else {
                return array(false, "更新失败：" . $this->getWriteDBError());
            }

        }
        return array(false, "更新失败");
    }

    /**
     * @desc 获取供应商名称根据供应商code
     * @return array()
     **@author Jackson
     * @parames string $code 供应商code
     * @Date 2019-02-13 17:01:00
     */
    public function get_supplier_name_bycode($code = '', $fields = '*')
    {
        //查询条件
        $condition = array();
        if ($code) {
            $condition['supplier_code'] = $code;
        }
        $rowData = $this->getDataByCondition($condition, $fields);
        if (!empty($rowData)) {
            return $rowData[0];
        }
        return [];
    }

    /**
     * @desc 获取创建人信息根据创建人ID
     * @return array()
     **@author Jackson
     * @parames array $params 参数
     * @Date 2019-02-22 17:01:00
     */
    public function get_create_user_name_by_useid($params = array())
    {

        //搜索条件
        $condition = [];
        $likeField = 'create_user_name';
        $limit = 40;
        if (isset($params[$likeField]) && !empty($params[$likeField])) {
            $condition["$likeField LIKE"] = $params[$likeField] . '%';
            $limit = 1000;
        }

        //排序
        $orderBy = '';

        //group by
        $groupBy = 'create_user_id';

        //查询字段
        $fields = 'create_user_id,create_user_name';
        $result = $this->getDataList($condition, $fields, $orderBy, 0, $limit, $groupBy);
        return $result['data'];

    }

    /**
     * @desc 供应商账期信息
     * @param array $params
     * @return array()
     * @author Jeff
     * @Date 2019/03/14 16:44
     */
    public function get_supplier_quota_inifo($params = array())
    {
        $sellerLoginId = $params['seller_login_id'];
        $this->load->library('alibaba/AliSupplierApi');

        $result = $this->alisupplierapi->getSupplierQuota(null, $sellerLoginId);

        if (!is_json($result)) {
            return array(false, "1688 接口返回结果数据错误[非JSON]");
        }
        $result = json_decode($result, true);
        if ((isset($result['errorCode']) and $result['errorCode'] !== null)
            or (isset($result['result']['errorCode']) and $result['result']['errorCode'] !== null)) {

            $message = isset($result['errorMessage']) ? $result['errorMessage'] : '';
            $message .= isset($result['result']['errorMsg']) ? $result['result']['errorMsg'] : '';
            return array(false, $message);
        } else {
            if (isset($result['result']['resultList']['accountPeriodList'][0])) {
                $return_data['tap_date_str'] = $result['result']['resultList']['accountPeriodList'][0]['tapDateStr'];//账期日期
                $return_data['quota'] = $result['result']['resultList']['accountPeriodList'][0]['quota'];//授信额度值，单位（分）
                $return_data['sur_plus_quota'] = $result['result']['resultList']['accountPeriodList'][0]['surplusQuota']; //剩余可用额度

                return $return_data;
            } else {
                return array(false, '数据项accountPeriodList缺失');
            }
        }
    }


    /**
     * 计划任务 从产品系统获取新增的供应商列表
     * @throws Exception
     * @author Jolon
     */
    public function plan_get_supplier_list()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->load->model('user/purchase_user_model');
        $this->load->model('Supplier_contact_model', 'contactModel');
        $this->load->model('Supplier_images_model', 'imagesModel');
        $this->load->model('supplier/Supplier_payment_info_model', 'paymentInfoModel');
        $this->load->model('supplier/Supplier_update_log_model', 'updateLogModel');





//        $old_get_result = $this->purchase_db->select('create_time')
//            ->where('record_type', 'plan_get_supplier_list')
//            ->order_by('id DESC')
//            ->get('api_request_log')
//            ->row_array();

        $supplier_type_map = [
            0 => 0,// 默认
            1 => 7,// 贸易商
            2 => 8,// 工厂
            3 => 0,
            4 => 0,
        ];
        $supplier_payment_map = [
            1 => 1,// 线上支付宝
            2 => 3,// 线下对公
            3 => 2,// 线下对私
            4 => 5,
            5 => 4,
            6 => 6,
        ];

        $supplier_source_map = [
            1 => 1,
            2 => 3,
            3 => 2,

        ];

        //develop_source

        $develop_source_arr = [
            0=>2,
            1=>1,
        ];
        //启用原因
        $enable_reason_arr = [
            1=>'开发新品',2=>'名下绑定SKU需要采购产品',3=>'失误操作，被禁用',4=>'其他'

        ];


        $create_time = date('Y-m-d H:i:s');
        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSupplier-selectSupplierInfoList');
        $access_token = getOASystemAccessToken();
        $request_url = $request_url . '?access_token=' . $access_token;

        $pageNumber = 0;
        $params = [
            'pageSize' => '2',
            'pageNumber' => $pageNumber,
            'status' => 2,// 1已创建 2待审核 3已审核 4被驳回 5待禁用 6已禁用
//            'createTimeFrom' => $create_time,//'2020-00-00 00:00:00',
//            'createTimeTo' => date('Y-m-d H:i:s'),
        ];
        $header = array('Content-Type: application/json');

        $flag = true;
        do {
            $pageNumber++;
            $params['pageNumber'] = $pageNumber;

            $results = getCurlData($request_url, json_encode($params), 'post', $header);
            $results = json_decode($results, true);





            if (isset($results['code'])) {
                if ($results['code'] == 200 and $results['data']) {
                    $results = $results['data']['records'];
                    if (empty($results)) {
                        $flag = false;
                        break;
                    }
                    foreach ($results as $key => $supplier) {

                        $this->purchase_db->trans_begin();

                        $contactMapList = isset($supplier['contactMapList']) ? $supplier['contactMapList'] : [];// 联系方式集合
                        $paymentMapList = isset($supplier['paymentMapList']) ? $supplier['paymentMapList'] : [];// 支付信息集合
                        $attacheMapList = isset($supplier['attacheMapList']) ? $supplier['attacheMapList'] : [];// 附件集合
                        $supplierMoreSettlementList = isset($supplier['supplierMoreSettlementList']) ? $supplier['supplierMoreSettlementList'] : [];//结算方式
                        $mapInfoList = isset($supplier['mapInfoList']) ? $supplier['mapInfoList'] : [];//关联供应商
                        $now_settlement = [];//目前的结算方式
                        $payment_optimization = 0;//财务是否优化


                        $new_supplier = [];
                        $now_supplier = [];
                        $new_supplier['supplier_code'] = $supplier['supplierCode'];
                        $new_supplier['supplier_name'] = $supplier['supplierName'];
                        $new_supplier['credit_code'] = $supplier ['creditCode'];

                        $supplier_code = $new_supplier['supplier_code'];
                        $supplier_name = $new_supplier['supplier_name'];
                        $new_supplier['settlement_type'] = $supplier['settleMethod'];
                        $new_supplier['supplier_settlement'] = $supplier['supplierSettlement'];



                        if (intval($supplier['taxPoint']) > 0) {// 税点为0就是否，税点不为零就默认为增值税发票
                            $new_supplier['invoice'] = '2';
                        } else {
                            $new_supplier['invoice'] = '1';
                        }
                        $new_supplier['invoice_tax_rate'] = $supplier['taxPoint'];
                        $new_supplier['supplier_type'] = $supplier_type_map[intval($supplier['type'])];// 公司类型 增加关系映射
                        $new_supplier['search_status'] = $supplier['isDel'] ? 2 : 1;// 搜索状态（1.启用,2.禁用）
                        $new_supplier['store_link'] = $supplier['webLink'];
                        $new_supplier['register_date'] = $supplier['regTime'];
                        $new_supplier['register_address'] = $supplier['regAddress'];
                        $new_supplier['create_time'] = $supplier['createTime'];
                        $new_supplier['modify_time'] = $supplier['modifyTime'];
                        $new_supplier['supplier_source'] = isset($supplier_source_map[$supplier['supplierResource']]) ? $supplier_source_map[$supplier['supplierResource']] : 0;
                        $new_supplier['develop_source']  = $develop_source_arr[$supplier['isPortal']]??0;
                        $new_supplier['platform_source']  = $supplier['platformType']??0;
                        $new_supplier['legal_person'] = isset($supplier['legalPerson']) ? $supplier['legalPerson'] : '';//法人代表
                        $new_supplier['is_agent'] = $supplier['type'] == 3 ? 1 : 0;//是否是代理商
                        $new_supplier['is_diversion'] = isset($supplier['isDiversion']) ? $supplier['isDiversion'] : 0;//是否是临时转常规
                        $shop_info = $this->get_shop_id($supplier['webLink']);
                        $shop_id = (isset($shop_info['data']['loginId']) && $shop_info['data']['loginId']) ? $shop_info['data']['loginId'] : '';
                        $is_cross_border = (isset($shop_info['data']['kuaJingBao']) && $shop_info['data']['kuaJingBao']) ? 1 : 0;//是否支持跨境宝
                        $new_supplier['shop_id'] = $shop_id;//店铺id
                        $new_supplier['is_cross_border'] = $is_cross_border;
                        $new_supplier['is_complete'] = 1;//新产品系统资料是否齐全 默认为"是"
                        $new_supplier['is_postage']   = $supplier['isPostage']??0;
                        //账期信息
                        $new_supplier['tap_date_str'] = !empty($paymentMapList[0]['paymentDays'])?$paymentMapList[0]['paymentDays']:'';
                        $new_supplier['quota'] = !empty($paymentMapList[0]['creditLine'])?$paymentMapList[0]['creditLine']:0;
                        $new_supplier['surplus_quota'] = !empty($paymentMapList[0]['ableCreditLine'])?$paymentMapList[0]['ableCreditLine']:0;




                        // 查找对应的人名
                        $devlopUser = !empty($supplier['devlopUser']) ? $supplier['devlopUser'] : $supplier['createUser'];
                        $create_user = $this->purchase_user_model->get_user_info_by_staff_code($devlopUser);
                        $new_supplier['create_user_id'] = isset($create_user['user_id']) ? $create_user['user_id'] : 0;
                        $new_supplier['create_user_name'] = isset($create_user['user_name']) ? $create_user['user_name'] : 0;

                        if ($supplier['modifyUser']) {
                            $create_user = $this->purchase_user_model->get_user_info_by_staff_code($supplier['modifyUser']);
                            $new_supplier['modify_user_id'] = isset($create_user['user_id']) ? $create_user['user_id'] : 0;
                            $new_supplier['modify_user_name'] = isset($create_user['user_name']) ? $create_user['user_name'] : 0;
                        }

                        // 判断供应商是否存在
                        $supplierOldInfo = [];
                        $old_supplier = $this->get_supplier_info($supplier_code, false);
                        if ($old_supplier) {

                            $supplierOldInfo = $this->find_supplier_one(null, $supplier_code);
                            $supplierOldInfo['supplier_basic'] = $old_supplier;


                            if ($new_supplier['is_diversion'] == 1) {//临时转常规
                                $new_supplier['supplier_source'] = 1;//来源转为常规
                             /*   if ($old_supplier['is_diversion_status'] == 1&&in_array($old_supplier['audit_status'], [SUPPLIER_WAITING_PURCHASE_REVIEW, SUPPLIER_WAITING_SUPPLIER_REVIEW, SUPPLIER_FINANCE_REVIEW])) {
                                    $this->update_supplier(['is_diversion'=>1, 'is_diversion_status' => 2],$old_supplier['id']);//转化为状态中
                                    //提交事务
                                    $this->purchase_db->trans_commit();
                                    continue;

                                }*/

                                //临时转常规流程  供应商只要是非待审状态 即可更新
                                if (in_array($old_supplier['audit_status'], [SUPPLIER_WAITING_PURCHASE_REVIEW, SUPPLIER_WAITING_SUPPLIER_REVIEW, SUPPLIER_FINANCE_REVIEW])) {
                                    $this->purchase_db->trans_rollback();//回滚事务 避免出现事务未提交/回滚 再次循环时又开启事务 导致更新不起作用问题
                                    continue;
                                }

                                if (!in_array($old_supplier['status'], [ 5, 1])) {//增加预禁用状态
                                    $this->purchase_db->trans_rollback();
                                    continue;
                                }
                            } else {
                                if (in_array($old_supplier['audit_status'], [SUPPLIER_WAITING_PURCHASE_REVIEW,SUPPLIER_WAITING_SUPPLIER_REVIEW, SUPPLIER_FINANCE_REVIEW])) {
                                    $this->purchase_db->trans_rollback();
                                    continue;// 待审核状态不更新
                                }
                                if (!in_array($old_supplier['status'], [2,5,6])) {// 待审，
                                    $this->purchase_db->trans_rollback();
                                    continue;
                                }
                            }

                         /*   $new_supplier['id'] = $old_supplier['id'];

                            $new_supplier['status'] = 4;//待审核$new_supplier['audit_status'] = SUPPLIER_WAITING_SUPPLIER_REVIEW;*/

                            //如果是更新供应商，需要更改供应商状态
                            $new_supplier_change_status_info = ['id'=>$old_supplier['id'],'status'=>4];

                            if ($new_supplier['is_diversion'] == 1) {
                                $new_supplier_change_status_info['is_diversion_status'] = 2;

                            }

                            list($_status, $_message) = $this->update_supplier($new_supplier_change_status_info);

                            //如果供应商名称变更，同步产品表和采购单表
                          /*  if ( $new_supplier['supplier_name'] !=$old_supplier['supplier_name']) {
                                $this->load->model('purchase/purchase_order_model');
                                $this->purchase_order_model->update_relate_supplier_name($supplier_code, $new_supplier['supplier_name']);

                            }*/

                        } else {

                            if ($supplier['supplierResource'] == 2) {
                                $new_supplier['supplier_level'] = 7;

                            } else {
                                $new_supplier['supplier_level'] = $this->cal_supplier_level($paymentMapList,$attacheMapList);

                            }
                            $new_supplier['status'] = 4;

                            //如果是第一次创建供应商，记录下信息
                            $create_data['basis_data'] = $new_supplier;

                            list($_status, $_message) = $this->insert_supplier($new_supplier);
                        }

                        if (empty($_status)) {
                            throw new Exception($_message);
                        }
                        $now_supplier['supplier_basic'] = $new_supplier;

                        // 删除旧数据
                        //  $this->purchase_db->where('supplier_code',$supplier_code)->delete('supplier_contact');
                        // $this->purchase_db->where('supplier_code',$supplier_code)->delete('supplier_payment_account');
                        $this->purchase_db->where('supplier_code', $supplier_code)->delete('supplier_images');

                        if ($contactMapList) {

                            $ship_address = [];
                            foreach ($contactMapList as $value) {
                                // 只支持 单个联系人
                               // $supplier_contact = $value;
                                //    $supplier_contact['id']             = -1;
                                $supplier_contact =[];
                                $supplier_contact['supplier_code'] = $supplier_code;
                                $supplier_contact['micro_letter'] = $value['wechat'];
                                $supplier_contact['contact_number'] = $value['phone'];
                                $supplier_contact['mobile'] = $value['mobile'];
                                $supplier_contact['qq'] = $value['qq'];
                                $supplier_contact['email'] = $value['email'];
                                $supplier_contact['contact_person'] = $value['contacts'];
                                $supplier_contact['contact_id'] = $value['contactId'];
                                $supplier_contact['foreign_city'] = isset($value['foreignCity']) ? $value['foreignCity'] : '';//国家编码
                                $supplier_contact['country_code'] = isset($value['countryCode']) ? $value['countryCode'] : '';//国外城市
                                $supplier_contact['want_want'] = $shop_id;//旺旺
                                if (empty($ship_address) and ($value['shippingProvince'] or $value['shippingAddress'])) {// 只取第一个不为空的 发货地址
                                    // 发货地址
                                    $ship_address['ship_province'] = $value['shippingProvince'];
                                    $ship_address['ship_city'] = $value['shippingCity'];
                                    $ship_address['ship_area'] = $value['shippingArea'];
                                    $ship_address['ship_address'] = $value['shippingAddress'];

                                    $new_supplier['ship_province'] = $value['shippingProvince'];
                                    $new_supplier['ship_city'] = $value['shippingCity'];
                                    $new_supplier['ship_area'] = $value['shippingArea'];
                                    $new_supplier['ship_address'] = $value['shippingAddress'];


                                    $now_supplier['supplier_basic']['ship_province'] = $value['shippingProvince'];
                                    $now_supplier['supplier_basic']['ship_city'] = $value['shippingCity'];
                                    $now_supplier['supplier_basic']['ship_area'] = $value['shippingArea'];
                                    $now_supplier['supplier_basic']['ship_address'] = $value['shippingAddress'];

                                    if (empty($old_supplier)) {
                                        $create_data['basis_data']['ship_province'] = $value['shippingProvince'];
                                        $create_data['basis_data']['ship_city'] = $value['shippingCity'];
                                        $create_data['basis_data']['ship_area'] = $value['shippingArea'];
                                        $create_data['basis_data']['ship_address'] = $value['shippingAddress'];

                                    }



                                }

                                $now_supplier['supplier_contact'][] = $supplier_contact;
                                if (empty($old_supplier)) {
                                    list($_contactStatus, $contactMsg) = $this->contactModel->update_supplier_contact($supplier_contact, $supplier_code);
                                    if (empty($_contactStatus)) {
                                        throw new Exception('供应商联系人据更新失败');
                                    }


                                }

                            }
                            if (empty($old_supplier)) {
                                $this->purchase_db->update($this->table_name, $ship_address, ['supplier_code' => $supplier_code]);
                            }

                        }

                        if ($paymentMapList) {
                            //映射
                            $is_tax_map = [//是否含税
                                1 => 0,
                                2 => 1,
                            ];
                            //定义
                            $new_payment_info_mirror=$old_payment_type = $new_payment_type = $insert_data = $update_data = $delete_data = [];

                            //采购系统的数据 old
                            $old_payment_info = $this->paymentInfoModel->get_payment_info_list($supplier_code);
                            foreach ($old_payment_info as $key => $item){
                                $tag = sprintf('%s%s%s',$item['supplier_code'],$item['is_tax'],$item['purchase_type_id']);
                                if (!isset($old_payment_type[$tag])){
                                    $old_payment_type[$tag] = $item['id'];
                                }

                                $history_payment_info[$item['is_tax']][$item['purchase_type_id']] = $item;
                            }

                            foreach ($paymentMapList as $key => $value) {
                                if (!isset($value['isTaxPoint']) || !isset($value['businessLine'])){
                                    throw new Exception('是否含税和业务线都不能为空');
                                }
                                $is_tax = $is_tax_map[$value['isTaxPoint']]??'';//是否含税
                                $purchase_type_id = $value['businessLine'];//业务线

                                $tag = sprintf('%s%s%s',$supplier_code,$is_tax,$purchase_type_id);
                                $new_payment_type[$tag] = [
                                    'supplier_code' => $supplier_code,
                                    'is_tax' => $is_tax,
                                    'purchase_type_id' => $purchase_type_id
                                ];

                                  //新产品系统推送的支付方式=线下境外  支付平台=富有
                                if (isset($supplier_payment_map[$value['payMethod']]) && $supplier_payment_map[$value['payMethod']] == 3) {
                                    $payment_platform = 6;
                                }
                                $now_settlement[] =  $value['supplierSettlement'];

                                $new_payment_info_mirror[$is_tax][$purchase_type_id] = [
                                    'supplier_code'=>$supplier_code,
                                    'payment_id'=>$value['paymentId'],
                                    'is_tax'=>$is_tax,
                                    'purchase_type_id' => $purchase_type_id,//业务线
                                    'settlement_type' => $value['settleMethod']??'',//结算方式(一级)
                                    'supplier_settlement' => $value['supplierSettlement']??'',//结算方式(二级)
                                    'payment_method' => (string)$supplier_payment_map[$value['payMethod']]?? 0,// 支付方式映射,//支付方式
                                    'account_name' => $value['receiveName']??'',//账号名称 收款人姓名
                                    'account' => $value['receiveAccount']??'',//银行账号 收款账号
                                    'currency' => $value['currency']??'',//币种
                                    'bank_address' => $value['bankAddress']??'',//开户行地址
                                    'swift_code' => $value['swiftCode']??'',//swift代码
                                    'email' => $value['pavpalEmail']??'',//邮箱

                                    //线上支付宝
                                    'store_name' => $value['shopName']??'',//店铺名称

                                    //线下境内
                                    'payment_platform_bank' => $value['masterBankName']??'',//开户行名称
                                    'payment_platform_branch' => $value['branchBankName']??'',//开户行名称(支行)

                                    //线下境外
                                    'payment_platform' =>  $payment_platform??'' ,//支付平台
                                    'phone_number' => $value['receiveMobile']??'',//收款人手机号
                                    'id_number' => $value['receiveIdNumber']??'',//收款人身份证号


                                ];



                                if (isset($old_payment_type[$tag])){//存在,修改
                                    $update_data[] = [
                                        //java id
                                        'payment_id' => $value['paymentId'],

                                        //财务结算信息
                                        'id' => $old_payment_type[$tag],//payment_info表的id

                                        'is_tax' => $is_tax,//是否含税
                                        'purchase_type_id' => $purchase_type_id,//业务线
                                        'settlement_type' => $value['settleMethod']??'',//结算方式(一级)
                                        'supplier_settlement' => $value['supplierSettlement']??'',//结算方式(二级)
                                        'payment_method' => (string)$supplier_payment_map[$value['payMethod']]?? 0,// 支付方式映射,//支付方式
                                        'account_name' => $value['receiveName']??'',//账号名称 收款人姓名
                                        'account' => $value['receiveAccount']??'',//银行账号 收款账号
                                        'currency' => $value['currency']??'',//币种
                                        'bank_address' => $value['bankAddress']??'',//开户行地址
                                        'swift_code' => $value['swiftCode']??'',//swift代码
                                        'email' => $value['pavpalEmail']??'',//邮箱

                                        //线上支付宝
                                        'store_name' => $value['shopName']??'',//店铺名称

                                        //线下境内
                                        'payment_platform_bank' => $value['masterBankName']??'',//开户行名称
                                        'payment_platform_branch' => $value['branchBankName']??'',//开户行名称(支行)

                                        //线下境外
                                        'payment_platform' =>  $payment_platform??'' ,//支付平台
                                        'phone_number' => $value['receiveMobile']??'',//收款人手机号
                                        'id_number' => $value['receiveIdNumber']??'',//收款人身份证号

                                        //更新信息
                                        'update_time' => date('Y-m-d H:i:s'),
                                        'update_user_name' => getActiveUserName(),
                                    ];

                                }else{//不存在,新增
                                    $insert_data[] = [
                                        //java id
                                        'payment_id' => $value['paymentId'],

                                        //财务结算信息
                                        'is_tax' => $is_tax,//是否含税
                                        'purchase_type_id' => $purchase_type_id,//业务线
                                        'supplier_code' => $supplier_code,//供应商编码
                                        'settlement_type' => $value['settleMethod']??'',//结算方式(一级)
                                        'supplier_settlement' => $value['supplierSettlement']??'',//结算方式(二级)
                                        'payment_method' => (string)$supplier_payment_map[$value['payMethod']]?? 0,// 支付方式映射,//支付方式
                                        'account_name' => $value['receiveName']??'',//账号名称 收款人姓名
                                        'account' => $value['receiveAccount']??'',//银行账号 收款账号
                                        'currency' => $value['currency']??'',//币种
                                        'bank_address' => $value['bankAddress']??'',//开户行地址
                                        'swift_code' => $value['swiftCode']??'',//swift代码
                                        'email' => $value['pavpalEmail']??'',//邮箱

                                        //线上支付宝
                                        'store_name' => $value['shopName']??'',//店铺名称

                                        //线下境内
                                        'payment_platform_bank' => $value['masterBankName']??'',//开户行名称
                                        'payment_platform_branch' => $value['branchBankName']??'',//开户行名称(支行)

                                        //线下境外
                                        'payment_platform' => $payment_platform??'',//支付平台
                                        'phone_number' => $value['receiveMobile']??'',//收款人手机号
                                        'id_number' => $value['receiveIdNumber']??'',//收款人身份证号

                                        //更新信息
                                        'update_time' => date('Y-m-d H:i:s'),
                                        'update_user_name' => getActiveUserName(),

                                        //创建信息
                                        'create_time' => date('Y-m-d H:i:s'),
                                        'create_user_name' => getActiveUserName(),
                                    ];
                                }

                            }

                            foreach ($old_payment_type as $key => $item){
                                if (!isset($new_payment_type[$key])){//删
                                    $delete_data[] = [
                                        'id' => $item,
                                        'is_del' => 1,
                                        //更新信息
                                        'update_time' => date('Y-m-d H:i:s'),
                                        'update_user_name' => getActiveUserName(),
                                    ];
                                }
                            }
                            if (!empty($old_supplier)&&!empty($update_data)) {
                                $payment_optimization = $this->check_payment_optimization(array_unique($now_settlement),$history_payment_info,$new_payment_info_mirror);

                            }
                         if (empty($old_supplier)) {
                             if(!empty($insert_data)){
                                 if (!$this->paymentInfoModel->insert_payment_info($insert_data)){
                                     throw new Exception('新增供应商财务结算信息失败');
                                 }
                             }
                             if(!empty($update_data)){
                                 if (!$this->paymentInfoModel->update_payment_info($update_data)){
                                     throw new Exception('更新供应商财务结算信息失败');
                                 }
                             }
                             if(!empty($delete_data)){
                                 if (!$this->paymentInfoModel->update_payment_info($delete_data)){
                                     throw new Exception('清除供应商财务结算信息失败');
                                 }
                             }

                             $create_data['payment_data'] = $new_payment_info_mirror;


                         }


                            //$new_supplier_payment_info = $this->paymentInfoModel->supplier_payment_info($supplier_code);
                            unset($old_payment_type ,$new_payment_type, $insert_data, $update_data, $delete_data);

                            $now_supplier['supplier_payment_info'] = $new_payment_info_mirror;

                        }

                        if ($attacheMapList) {
                            foreach ($attacheMapList as $value) {
                                $busine_licen = $value['blUrl'];
                                $verify_book = $value['gtcUrl'];
                                $bank_information = $value['bRRul'];
                                $collection_order = $value['tcaUrl'];
                                $idcard_front = $value['idfUrl'];//身份证正面
                                $idcard_back = $value['idrUrl'];//身份证反面
                                $company_reg = $value['crUrl'];//公司注册书
                                $tax_cer = $value['taxCertUrl'];//税务证明文件
                                $other = $value['otherUrl'];//其他
                                $cor_letter = $value['cLetterUrl'];
                                $integrity_agreement = $value['hUrl'];//廉洁协议

                                $image_list = [];
                                if ($busine_licen) $image_list['busine_licen'] = $busine_licen;
                                if ($verify_book) $image_list['verify_book'] = $verify_book;
                                if ($bank_information) $image_list['bank_information'] = $bank_information;
                                if ($collection_order) $image_list['collection_order'] = $collection_order;
                                if ($idcard_front) $image_list['idcard_front'] = $idcard_front;
                                if ($idcard_back) $image_list['idcard_back'] = $idcard_back;
                                if ($company_reg) $image_list['company_reg'] = $company_reg;
                                if ($tax_cer) $image_list['tax_cer'] = $tax_cer;
                                if ($cor_letter) $image_list['cooperation_letter'] = $cor_letter;
                                if ($integrity_agreement) $image_list['integrity_agreement'] = $integrity_agreement;


                                if ($other) {
                                    $other = str_replace("|",";",$other);
                                    $image_list['other_proof'] = $other;
                                }

                                if ($image_list) {
                                    foreach ($image_list as $image_type => $image_url) {
                                        $image_info = [];
                                        $image_info['supplier_code'] = $supplier_code;
                                        $image_info['supplier_name'] = $supplier_name;
                                        $image_info['image_type'] = $image_type;
                                        $image_info['image_url'] = $image_url;

                                        $now_supplier['supplier_image'][] = $image_info;
                                        list($_imageStatus, $imageMsg) = $this->imagesModel->update_supplier_image($image_info, $supplier_code);
                                        if (empty($_imageStatus)) {
                                            throw new Exception('供应商图片数据更新失败');
                                        }
                                    }
                                }
                            }

                        }

                        //关联供应商
                        $new_relation_suppliers = [];//新关联供应商
                        $old_relation_suppliers = $this->supplier_model->get_relation_supplier_detail($supplier_code,false);

                        if (!empty($mapInfoList)) {
                            foreach ($mapInfoList as $relation_info) {
                               $temp=[];
                                $temp['supplier_code']=$supplier_code;
                                $temp['relation_code']=$relation_info['mapSupplierCode'];
                                $temp['supplier_type']=$relation_info['relatedType'];
                                $temp['supplier_reason']=$relation_info['bindReasonType'];
                                $temp['supplier_type_remark']=$relation_info['relatedTypeRemark'];
                                $temp['supplier_reason_remark']=$relation_info['bindReasonRemark'];
                                $temp['other_images'] = '';// 数据格式统一
                                $new_relation_suppliers[] = $temp;

                            }

                        }

                        if (empty($old_supplier)) {
                            if (!empty($new_relation_suppliers)) {
                                $this->supplier_model->update_relation_suppplier($supplier_code,$new_relation_suppliers,1);
                            } elseif (empty($new_relation_suppliers)&&!empty($old_relation_suppliers)) {
                                $this->supplier_model->update_relation_suppplier($supplier_code,[],2);

                            }

                        }

                        $now_supplier['relation_supplier'] = $new_relation_suppliers;










                        //更新供应商资料是否齐（is_complete）
                        //$this->get_is_complete($supplier_code);


                        $cancel_disabled = 0;
                        if ($old_supplier) {

                            if (in_array($old_supplier['status'],[2,6])) {
                                $operate_type = SUPPLIER_RESTART_FROM_PRODUCT;
                                $cancel_disabled = 1;
                                $supplier_audit_type = 3;//启用
                            } elseif($new_supplier['is_diversion'] == 1){
                                $operate_type = SUPPLIER_UPDATE_FROM_PRODUCT;
                                $supplier_audit_type = 5;//临时转常规

                            } else {
                                $operate_type = SUPPLIER_UPDATE_FROM_PRODUCT;
                                $supplier_audit_type = 2;//更新
                            }
                            $create_user_id = isset($new_supplier['modify_user_id']) ? $new_supplier['modify_user_id'] : $new_supplier['create_user_id'];
                            $create_user_name = isset($new_supplier['modify_user_name']) ? $new_supplier['modify_user_name'] : $new_supplier['create_user_name'];

                        } else {
                            $operate_type = SUPPLIER_CREATE_FROM_PRODUCT;
                            $create_user_id = $new_supplier['create_user_id'];
                            $create_user_name = $new_supplier['create_user_name'];
                            $supplier_audit_type = 1;//创建
                        }



                        // 操作提交人
                        $create_user        = $this->purchase_user_model->get_user_info_by_staff_code($supplier['submitUser']);
                        $submit_user_id     = isset($create_user['user_id']) ? $create_user['user_id'] : 0;
                        $submit_user_name   = isset($create_user['user_name']) ? $create_user['user_name'] : 0;
                        $submit_time        = isset($supplier['submitTime'])?$supplier['submitTime']:'';

                        /*$all_change_data = [
                            'change_data' => $change_data,
                            'insert_data' => $insert_data,
                            'delete_data' => $delete_data,
                            'change_data_log' => $change_data_log,
                        ];
                        */
                        $return = $this->get_supplier_update_log_from_product($supplierOldInfo, $now_supplier,$operate_type);
                     


                        if (empty($old_supplier)) {
                            $return['change_data'] = [];
                            $return['create_data'] = $create_data;

                        }

                        //$return['change_data'] = [];//产品系统推送采购系统已存在的供应商  在上面数据已经更新 不再记录到更新日志表
                        $return['change_data']['change_data']['cancel_disabled'] = $cancel_disabled;//是否是产品系统发起的启用流程
                        $return['change_data']['change_data']['before_check_status'] = isset($old_supplier['status']) ? $old_supplier['status'] : '';
                        // 生成一条 供应商数据变更待审核记录


                        // 创建时取创建人，其他优先取 提交人->更新人->默认值
                        if($operate_type == SUPPLIER_CREATE_FROM_PRODUCT){// 产品系统创建供应商
                            $create_user = $create_user_name;
                            $create_user_id = $create_user_id;
                        }elseif($submit_user_name){// 产品系统更新供应商提交人
                            $create_user = $submit_user_name;
                            $create_user_id = $submit_user_id;
                        }elseif(!empty($create_user_id) and !empty($create_user_name)){
                            $create_user = $create_user_name;
                            $create_user_id = $create_user_id;
                        }else{
                            $create_user = 'admin';
                            $create_user_id = 1;
                        }
                        $create_time = $submit_time?$submit_time:date('Y-m-d H:i:s');

                        $insert_data = [
                            'supplier_code' => $new_supplier['supplier_code'],
                            'action' => 'supplier/supplier/collect_update_supplier',
                            'message' => json_encode($return['change_data']),
                            'need_finance_audit' => 1,
                            'audit_status' => SUPPLIER_WAITING_SUPPLIER_REVIEW,
                            'create_user_id' => $create_user_id ?: 1,
                            'create_user_name' => $create_user,
                            'create_time' => $create_time,
                            'source' => 2,// 2.设置记录为产品系统同步的更新
                            'apply_type' =>$supplier_audit_type,
                            'apply_no'         =>$this->get_prefix_new_number('gy'),
                            'enable_remark'    =>$enable_reason_arr[$supplier['enableReasonType']]??'',
                            'enable_desc'      =>$supplier['disableReason']??'',
                            'payment_optimization'=>$payment_optimization




                        ];

                        $result = $this->updateLogModel->insert_one($insert_data,$return['insert_payment_log']);


                        if (empty($result)) {
                            throw new Exception("保存更新的数据失败");
                        }

                        operatorLogInsert(
                            [
                                'id' => $supplier_code,
                                'type' => 'supplier_update_log',
                                'content' => getSupplierOperateType($operate_type),
                                'detail' => $old_supplier ? json_encode(isset($return['change_data_log']) ? $return['change_data_log'] : []) : "创建(产品系统)",
                                'operate_type' => $operate_type,
                                'user' => $create_user,
                                'time' => $create_time,
                            ]
                        );

                        $this->load->model('supplier/Supplier_audit_results_model');
                        $this->Supplier_audit_results_model->create_record($supplier_code, $supplier_audit_type);// 创建供应商审核记录 类型为新建

                        if ($this->purchase_db->trans_status() === FALSE) {
                            $this->purchase_db->trans_rollback();
                            throw new Exception('供应商数据更新事务出错');
                        } else {
                            $this->purchase_db->trans_commit();
                        }

                    }

                } else {
                    throw new Exception($results['msg']);
                }
            } else {
                throw new Exception('执行出错');
            }

            apiRequestLogInsert(
                [
                    'record_type' => 'plan_get_supplier_list_page',
                    'api_url' => $request_url,
                    'post_content' => $params,
                    'response_content' => $results,
                    'create_time' => $create_time,
                ]);

        } while ($flag);
        apiRequestLogInsert(
            [
                'record_type' => 'plan_get_supplier_list',
                'api_url' => $request_url,
                'post_content' => $params,
                'response_content' => [],
                'create_time' => $create_time,
            ]);
        return [true, '执行成功'];

    }


    /**
     * 根据供应商编码更新供应商审核状态到产品系统
     * @param      $supplier_code
     * @param null $status
     * @return array
     * @author Jolon
     */
    public function plan_update_supplier_status($supplier_code, $status = null, $remark = '', $cancel_disabled = 0,$apply_type=0)
    {
        $return = ['code' => false, 'data' => '', 'message' => ''];

        $audit_status = null;
        $supplier = $this->get_supplier_info($supplier_code, false);
        if (empty($supplier)) { //or $supplier['source'] != 3 非产品系统创建的供应商 无需推送
            $return['code'] = true;
            return $return;
        }
        if (is_null($status)) {
            $status = $supplier['status'];
            $audit_status = $supplier['audit_status'];
        }

        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSupplier-updateBystatus');
        $access_token = getOASystemAccessToken();
        $request_url = $request_url . '?access_token=' . $access_token;

        // 根据 审核状态  来判断状态
//        if(in_array($audit_status,[SUPPLIER_PURCHASE_REJECT,SUPPLIER_SUPPLIER_REJECT,SUPPLIER_FINANCE_REJECT])){
//            $status = 4;// 已驳回
//            $record = $this->get_latest_audit_remark($supplier_code,2);// 获取驳回备注信息
//        }elseif($audit_status == SUPPLIER_REVIEW_PASSED){
//            $status = 3;// 通过
//            $record = $this->get_latest_audit_remark($supplier_code,1);// 获取驳回备注信息
//        }else{
//            $return['code'] = true;
//            return $return;
//        }

        $is_diversion = 0;

        if ($supplier['is_diversion'] == 1) {
            $is_diversion = 1;
        }

        if ($status == 1) {
            $status = 3;// 通过
            $record = $this->get_latest_audit_remark($supplier_code, 1);// 获取驳回备注信息
            $this->load->model('Supplier_update_log_model');
            $update_log = $this->Supplier_update_log_model->get_latest_audit_result($supplier_code);
            $message = json_decode($update_log['message'], true);
            $change_data = isset($message['change_data']) ? $message['change_data'] : null;
            $supplier_basic = (isset($change_data['basis_data']) && $change_data['basis_data']) ? $change_data['basis_data'] : [];
            if (isset($supplier_basic['supplier_source']) && $supplier_basic['supplier_source'] == 1) {
                $is_diversion = 1;//临时转常规
            }
        } elseif (in_array($status,[5,7,12])) {
            if ($status == 5) {
                if ($apply_type == 5) {
                    $status = 3;
                } else {
                    $status = 4;

                }


            }
            $record = $this->get_latest_audit_remark($supplier_code, 2);// 获取驳回备注信息
        } elseif ($status == 2) {
            $status = 2;//待审核状态
        } else {
            $return['code'] = true;
            return $return;
        }
        $remark1 = isset($record['content']) ? $record['content'] : '';
        $remark2 = isset($record['content_detail']) ? $record['content_detail'] : '';
        $remark1 = $remark1 ? $remark1 . ',' . $remark2 : $remark1;
        $operator_id = isset($record['operator_id']) ? $record['operator_id'] : (getActiveUserId() ? getActiveUserId() : '');
        $operator = isset($record['operator']) ? $record['operator'] : '';
        $this->load->model('user/purchase_user_model');
        $user_info = $this->purchase_user_model->get_user_info_by_id($operator_id);

        $params = [
            'supplierCode' => $supplier_code,
            'status' => $status,
            'createUser' => isset($user_info['staff_code']) ? $user_info['staff_code'] : $operator,
            'createTime' => isset($record['operate_time']) ? $record['operate_time'] : date('Y-m-d H:i:s'),
            'modifyReason' => $remark ? $remark : $remark1,
            'isDiversion' => $is_diversion,//是否是临时转常规
            'cancelDisabled' => $cancel_disabled//是否是产品系统发起的 取消禁用 流程
        ];
        $header = array('Content-Type: application/json');

        $results = getCurlData($request_url, json_encode($params), 'post', $header);
        $results = json_decode($results, true);
        if (isset($results['code'])) {
            if ($results['code'] == 200) {
                $return['code'] = true;
                $status = '1';
            } else {
                $return['message'] = $results['msg'];
                $status = '0';
            }
            apiRequestLogInsert(
                [
                    'record_type' => 'plan_update_supplier_status',
                    'record_number' => $supplier_code,
                    'api_url' => $request_url,
                    'post_content' => $params,
                    'response_content' => $results,
                    'status' => $status,
                ]);
        } else {
            $return['message'] = '执行出错';
        }

        return $return;
    }


    /**
     * 推送审核更新之后供应商信息到产品系统plan_push_update_supplier_info
     * @param $supplier_code
     * @return array
     * @author Jolon
     */
    public function plan_push_update_supplier_info($supplier_code)
    {
        $return = ['code' => false, 'data' => '', 'message' => ''];
        $this->load->model('supplier/Supplier_address_model');
        $this->load->model('supplier/Supplier_contact_model');
        $this->load->model('user/purchase_user_model');

        $supplier = $this->get_supplier_info($supplier_code, true);
        $disable_reason = '';
        if ($supplier['status'] == IS_DISABLE) {//禁用状态下禁用原因
            $disable_record = $this->purchase_db->select('content_detail')
                ->where_in('record_type', ['SUPPLIER_UPDATE_LOG', 'PUR_SUPPLIER'])
                ->where('operate_route', 'supplier/Supplier/supplier_disable')
                ->where('content', '供应商禁用成功')
                ->where('record_number', $supplier_code)
                ->order_by('id', 'desc')
                ->get('pur_operator_log')
                ->row_array();
            $disable_reason = isset($disable_record['content_detail']) ? $disable_record['content_detail'] : '';
        }
        if (empty($supplier)) {// 非产品系统创建的供应商 无需推送
            $return['code'] = true;
            return $return;
        }

        // 采购系统 状态：状态（1正常,2禁用,3删除,4待审,5审核不通过）
        // 产品系统 状态：1已创建 2待审核 3已审核 4被驳回 5待禁用 6已取消 7 已禁用
        $supplier_status_map = [// 状态映射关系
            1 => 3,
            2 => 7,
            3 => 6,
            4 => 2,
            5 => 4,
        ];
        $supplier_type_map = [
            0 => 0,// 默认
            7 => 1,// 贸易商
            8 => 2,// 工厂
        ];

        $supplier_payment_map = [
            1 => 1,// 线上支付宝
            2 => 3,// 线下对公
            3 => 2,// 线下对私
            4 => 5,
            5 => 4,
            6 => 6,
        ];

        $supplier_level_arr = getSupplierLevel();

        $supplierType = isset($supplier_type_map[$supplier['supplier_type']]) ? $supplier_type_map[$supplier['supplier_type']] : $supplier['supplier_type'];// 映射
        $supplier_grade = isset($supplier_level_arr[$supplier['supplier_level']]) ? $supplier_level_arr[$supplier['supplier_level']] : '';// 映射
        //关联供应商
        $mapInfoList = [];

        if (!empty($supplier['relation_supplier'])) {
            foreach ($supplier['relation_supplier'] as $relation) {
                $temp['supplierCode']=$relation['supplier_code'];
                $temp['mapSupplierCode']=$relation['relation_code'];
                $temp['relatedType']   = $relation['supplier_type'];
                $temp['bindReasonType'] =$relation['supplier_reason'];
                $temp['relatedTypeRemark']=$relation['supplier_type_remark'];
                $temp['bindReasonRemark']=$relation['supplier_reason_remark'];
                $mapInfoList[]  = $temp;

            }

     }


        //获取供应商联系方式
        $contactList = [];
        foreach ($supplier['contact_list'] as $row) {
            $temp['id'] = $row['contact_id'];
            $temp['procurementId'] = $row['id'];
            $temp['mobile'] = $row['contact_number'];
            $temp['phone'] = $row['mobile'];
            $temp['wechat'] = $row['micro_letter'];
            $temp['contacts'] = $row['contact_person'];
            $temp['qq'] = $row['qq'];
            $temp['email'] = $row['email'];
            $temp['shippingProvince'] = $supplier['ship_province'];
            $temp['shippingCity'] = $supplier['ship_city'];
            $temp['shippingArea'] = $supplier['ship_area'];
            $temp['shippingAddress'] = $supplier['ship_address'];
            $temp['createUser'] = $row['create_user_name'];
            $temp['createTime'] = empty(strtotime($row['create_time'])) ? "" : $row['create_time'];
            $temp['modifyUser'] = $row['modify_user_name'];
            $temp['modifyTime'] = empty(strtotime($row['modify_time'])) ? "" : $row['modify_time'];
            $temp['dataSources'] = 2;//1产品系统 2采购系统
            $temp['countryCode'] = $row['country_code'];//国家编码
            $temp['foreignCity'] = $row['foreign_city'];//国外城市
            $contactList[] = $temp;
            unset($temp);
        }
        //获取供应商支付信息
        $paymentList = [];
//        pr($supplier['supplier_payment_info']);exit;
        $is_tax_map = [
            0 => 1,
            1 => 2,
        ];
        foreach ($supplier['supplier_payment_info'] as $is_tax => $value) {
            foreach ($value as $business_line => $vv){
                $res['id'] = $vv['payment_id'];
                $res['procurementId'] = $vv['id'];
                $res['isTaxPoint'] = $is_tax_map[$is_tax]??'';//是否含税
                $res['businessLine'] = $business_line;//业务线
                $res['payMethod'] = $supplier_payment_map[$vv['payment_method']]??0;//支付方式
                $res['shopName'] = $vv['store_name'];
                $res['receiveName'] = $vv['account_name'];
                $res['receiveAccount'] = $vv['account'];
                $res['receiveMobile'] = $vv['phone_number'];
                $res['receiveIdNumber'] = $vv['id_number'];
                $res['idType'] = 1;//证件类型：1身份证 2…，默认1
                $res['isDel'] = 0;//删除标记，1：已删除，0：保留的
                $res['createUser'] = $vv['create_user_name'];
                $res['createTime'] = empty(strtotime($vv['create_time'])) ? "" : $vv['create_time'];
                $res['modifyUser'] = $vv['update_user_name'];
                $res['modifyTime'] = empty(strtotime($vv['update_time'])) ? "" : $vv['update_time'];
                $res['dataSources'] = 2;//1产品系统 2采购系统

                $res['masterBankName'] = $vv['payment_platform_bank'];//主行
                $res['branchBankName'] = $vv['payment_platform_branch'];//支行
                $res['bankAddress'] = $vv['bank_address'];//开户行地址
                $res['currency'] = $vv['currency'];//币种
                $res['swiftCode'] = $vv['swift_code'];//swift代码
                $res['pavpalEmail'] = $vv['email'];//邮箱

                $res['settlement_type'] = $vv['settlement_type'];//结算方式一级
                $res['supplier_settlement'] = $vv['supplier_settlement'];//结算方式二级
                $paymentList[] = $res;
                unset($res);
            }

        }


        $audit_list = isset($supplier['audit_list']) ? $supplier['audit_list'] : [];
        $log_createUserId = $audit_list ? $audit_list['create_user_id'] : 0;
        $user_info = $this->purchase_user_model->get_user_info_by_id($log_createUserId);
        $create_user_info = $this->purchase_user_model->get_user_info_by_id($supplier['create_user_id']);
        $modify_user_info = $this->purchase_user_model->get_user_info_by_id($supplier['modify_user_id']);
        $create_user_staff_code = isset($create_user_info['staff_code']) ? $create_user_info['staff_code'] : '';
        $log_staff_code = isset($user_info['staff_code']) ? $user_info['staff_code'] : '';
        $modify_user_staff_code = isset($modify_user_info['staff_code']) ? $modify_user_info['staff_code'] : '';
        if (false and empty($user_info)) {// 不做拦截了
            apiRequestLogInsert(
                [
                    'record_number' => $supplier_code,
                    'record_type' => 'plan_push_update_supplier_info',
                    'api_url' => '推送供应商失败-用户工号缺失',
                    'post_content' => $log_createUserId,
                    'response_content' => $user_info,
                    'status' => 0,
                ]);
            $return['message'] = '推送供应商失败-用户工号缺失';
        }

        //获取供应商图片
        $supplier_image = [// 设置默认 空值（解决JAVA解析问题）
            'blUrl' => '',
            'tcaUrl' => '',
            'gtcUrl' => '',
            'bRRul' => '',
            'idfUrl' => '',
            'idrUrl' => '',
            'crUrl' => '',
            'taxCertUrl' => '',
            'otherUrl' => '',
            'cLetterUrl'=>''
        ];

        foreach ($supplier['images_list'] as $val) {
            if (is_array($val['image_url']) and $val['image_type'] != 'other_proof'){
                $val['image_url'] = array_shift($val['image_url']);//一种类型图片存在多条时  取第一条推送
            }

            if (!empty($val)) {
                $supplier_image['createUser'] = $create_user_staff_code;
                $supplier_image['createTime'] = $supplier['create_time'];
                $supplier_image['modifyUser'] = $modify_user_staff_code;
                $supplier_image['modifyTime'] = $supplier['modify_time'];
            }
            if ($val['image_type'] == 'busine_licen') {
                $supplier_image['blUrl'] = $val['image_url'];
            }
            if ($val['image_type'] == 'collection_order') {
                $supplier_image['tcaUrl'] = $val['image_url'];
            }
            if ($val['image_type'] == 'verify_book') {
                $supplier_image['gtcUrl'] = $val['image_url'];
            }
            if ($val['image_type'] == 'bank_information') {
                $supplier_image['bRRul'] = $val['image_url'];
            }

            if ($val['image_type'] == 'idcard_front') {//身份证正面
                $supplier_image['idfUrl'] = $val['image_url'];
            }

            if ($val['image_type'] == 'idcard_back') {//身份证反面
                $supplier_image['idrUrl'] = $val['image_url'];
            }

            if ($val['image_type'] == 'company_reg') {//公司注册书
                $supplier_image['crUrl'] = $val['image_url'];
            }

            if ($val['image_type'] == 'tax_cer') {//税务证明文件
                $supplier_image['taxCertUrl'] = $val['image_url'];
            }

            if ($val['image_type'] == 'other_proof') {//其他
                if(is_array($val['image_url'])){
                    $supplier_image['otherUrl'] = implode('|',$val['image_url']);// 支持多张图片
                }else{
                    $supplier_image['otherUrl'] = $val['image_url'];
                }
            }
            if ($val['image_type'] == 'cooperation_letter') {//其他
                $supplier_image['cLetterUrl'] = $val['image_url'];
            }
        }

        $audit_status = $audit_list ? $audit_list['audit_status'] : 0;
        switch ($audit_status) {
            case SUPPLIER_PURCHASE_REJECT:
                $auditRejectUser = $audit_list['purchase_audit'];
                $auditRejectTime = $audit_list['purchase_time'];
                $auditRejectReason = '采购驳回：' . $audit_list['purchase_note'];
                break;
            case SUPPLIER_SUPPLIER_REJECT:
                $auditRejectUser = $audit_list['supply_chain_audit'];
                $auditRejectTime = $audit_list['supply_chain_time'];
                $auditRejectReason = '供应链驳回：' . $audit_list['supply_chain_note'];
                break;
            case SUPPLIER_FINANCE_REJECT:
                $auditRejectUser = $audit_list['finance_audit'];
                $auditRejectTime = $audit_list['finance_time'];
                $auditRejectReason = '采购驳回：' . $audit_list['finance_note'];
                break;
            default :
                $auditRejectUser = "";
                $auditRejectTime = '0000-00-00 00:00:00';
                $auditRejectReason = '审核状态有误，获取备注失败';
        }
        $auditRejectUser = $this->purchase_user_model->get_user_info_by_id($auditRejectUser);


        $params = [
            'prodSupplier' => [
                'creditCode' => $supplier['credit_code'],
                'supplierName' => $supplier['supplier_name'],
                'supplierCode' => $supplier['supplier_code'],
                'settleMethod' => $supplier['settlement_type'],
                'supplierSettlement' => $supplier['supplier_settlement'],
                'status' => isset($supplier_status_map[$supplier['status']]) ? $supplier_status_map[$supplier['status']] : 3,
                'taxrate' => $supplier['taxrate'],
                //'invoice'              => $supplier['invoice'],
                'createUser' => $create_user_staff_code,
                'createTime' => $supplier['create_time'],
                'taxPoint' => $supplier['invoice_tax_rate'],
                'type' => $supplierType,
                'isDel' => $supplier['status'] == 3 ? 1 : 0,
                'devlopUser' => $create_user_staff_code,
                'modifyUser' => $modify_user_staff_code,
                'modifyTime' => empty(strtotime($supplier['modify_time'])) ? "" : $supplier['modify_time'],
                'checkUser' => $supplier['audit_list']['finance_audit'],
                'checkTime' => $supplier['audit_list']['finance_time'],
                'webLink' => $supplier['store_link'],
                'rejectUser' => isset($auditRejectUser['staff_code']) ? $auditRejectUser['staff_code'] : '',
                'rejectReason' => $auditRejectReason,
                'rejectTime' => $auditRejectTime,
                'supplierResource' => $supplier['supplier_source'],
                'regAddress' => $supplier['register_address'],
                'regTime' => $supplier['register_date'],
                'legalPerson' => $supplier['legal_person'],
                'disableReason' => $disable_reason,//禁用原因
                'isDiversion' => $supplier['is_diversion'],
                'supplierGrade'=>$supplier_grade,
                'isPostage'=>$supplier['is_postage']



            ],
            'prodSupplierContactList' => $contactList,
            'prodSupplierPaymentList' => $paymentList,
            'prodSupplierAttache' => $supplier_image,
            'mapInfoList' =>$mapInfoList,
            'prodSupplierLog' => [
                'createTime' => empty(strtotime($supplier['audit_list']['create_time'])) ? "" : $supplier['audit_list']['create_time'],
                'createUser' => $log_staff_code,//isset($user_info['staff_code']) ? $user_info['staff_code'] : $supplier['audit_list']['create_user_name'],
            ]
        ];
        $header = array('Content-Type: application/json');
        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSupplier-updateSupplierInfo');
        $access_token = getOASystemAccessToken();
        $request_url = $request_url . '?access_token=' . $access_token;
        $results = getCurlData($request_url, json_encode($params), 'post', $header);
        $results = json_decode($results, true);
        if (isset($results['code'])) {
            if ($results['code'] == 200) {
                //绑定映射关系
                if (isset($results['data']) && !empty($results['data'])) {
                    if (isset($results['data']['contact']) && !empty($results['data']['contact'])) {
                        //映射供应商联系表
                        $contact = $results['data']['contact'];
                        foreach ($contact as $va) {
                            $res = $this->Supplier_contact_model->update_contact_id($va['productId'], $va['procurementId']);
                            if (empty($res)) {
                                $return['message'] = '绑定供应商映射关系失败';
                                $status = '0';
                            }
                        }
                    }
                    //绑定映射关系供应商支付方式
                    if (isset($results['data']['payment']) && !empty($results['data']['payment'])) {
                        //映射供应商联系表
                        $payment = $results['data']['payment'];
                        foreach ($payment as $va) {
                            $res = $this->Supplier_payment_info_model->update_payment_id($va['productId'], $va['procurementId']);
                            if (empty($res)) {
                                $return['message'] = '绑定供应商映射关系失败';
                                $status = '0';
                            }
                        }
                    }
                }
                $return['code'] = true;
                $status = '1';
            } else {
                $return['message'] = $results['msg'];
                $status = '0';
            }


        } else {
            $return['message'] = '执行出错';
        }

        apiRequestLogInsert(
            [
                'record_number' => $supplier_code,
                'record_type' => 'plan_push_update_supplier_info',
                'api_url' => $request_url,
                'post_content' => $params,
                'response_content' => $results,
                'status' => $status,
            ]);

        return $return;
    }


    /**
     * @desc : 逻辑删除供应商图片信息
     * @param : image_id     int    删除图片的ID
     * @return : 成功返回true, 失败返回False
     **/

    public function del_supplier_image($image_id)
    {

        if (empty($image_id)) {
            return False;
        }
        $result = $this->purchase_db
            ->where('id', $image_id)
            ->update($this->pur_supplier_images, ['image_status' => 3]);
        if ($result) {

            return True;
        }
        return False;
    }

    public function get_supplier_images($image_ids)
    {
        if (empty($image_ids)) {
            return NULL;
        }
        $result = $this->purchase_db->select('image_url,supplier_name,image_type')->where_in('id', $image_ids)->get($this->pur_supplier_images)->row_array();
        return $result;

    }

    /** 供应商状态变更时同步至产品系统 wangliang
     * @param $supplier_code 供应商编码
     * @param $status 变更后的状态
     * @param string $remark 审批备注
     * @param string $log_update 是否从更新日志数据中查找数据
     * @return array
     */
    public function send_supplier_status($supplier_code, $status, $remark = '', $log_update = false)
    {
        try {
            if (!$supplier_code || !$status) {
                throw new Exception('非法请求');
            }

            $supplierInfo = $this->get_supplier_info($supplier_code, false);
            if (!$supplierInfo) throw new Exception('供应商信息不存在');

            if (!$supplierInfo['supplier_name']) throw new Exception('供应商名称不存在');

            // $product_url = getConfigItemByName('api_config', 'java_system_erp', 'pushPurSupplierToErp'); //获取推送供应商url
            $product_url = "http://rest.java.yibainetwork.com/erp/providerErpController/pushPurSupplierToErp"; //获取推送供应商url
            // $access_taken = getOASystemAccessToken();
            $access_taken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJyZWFkIl0sImV4cCI6MTYyMDI3MTMwMSwiYXV0aG9yaXRpZXMiOlsiMCJdLCJqdGkiOiIxYmI4OGZkNS1mZjhhLTRiZDUtOGEzYi02YzI5ODRmODIxZmUiLCJjbGllbnRfaWQiOiJwcm9kX3Byb2N1cmVtZW50In0.gNssHXE_Kc5NZV5DgD_zxbYO3JAx0PDUfwaGj3Hy7kw";
            if (empty($product_url)) {
                return ['code' => false, 'message' => '产品系统api不存在'];
            }
            if (empty($access_taken)) {
                return ['code' => false, 'message' => '获取access_token值失败'];
            }

            $supplier_name = $supplierInfo['supplier_name'];
            $supplier_type = $supplierInfo['supplier_type'];
            if ($log_update) {
                $this->load->model('supplier/Supplier_update_log_model');
                $audit_remark = $this->Supplier_update_log_model->get_latest_audit_result($supplier_code);
                $purchase_note = isset($audit_remark['purchase_note']) ? $audit_remark['purchase_note'] : '';
                $supply_chain_note = isset($audit_remark['supply_chain_note']) ? $audit_remark['supply_chain_note'] : '';
                $finance_note = isset($audit_remark['finance_note']) ? $audit_remark['finance_note'] : '';

                $remark = $finance_note ?: ($supply_chain_note ?: $purchase_note);
            }

            $this->load->model('Supplier/Supplier_address_model', 'addressModel');

            $data_post = [
                'supplierName' => $supplier_name,
                'supplierType' => $supplier_type,
                'supplierCode' => $supplier_code,
                'status' => $status,
                'nohasCause' => $remark,
                'shipProvince' => $this->addressModel->get_address_name_by_id($supplierInfo['ship_province']),
                'shipCity' => $this->addressModel->get_address_name_by_id($supplierInfo['ship_city']),
                'shipArea' => $this->addressModel->get_address_name_by_id($supplierInfo['ship_area']),
                'shipAddress' => $supplierInfo['ship_address']
            ];
            $url_api = $product_url . "?access_token=" . $access_taken;
            $results = getCurlData($url_api, json_encode($data_post), 'post', array('Content-Type: application/json'));
            $product_result = json_decode($results, true);
            if (!isset($product_result['code']) || $product_result['code'] != 200) throw new Exception(isset($product_result['msg']) ? $product_result['msg'] : '数据同步失败');

            // apiRequestLogInsert(
            //     [
            //         'record_number' => $supplier_code,
            //         'record_type' => '新系统更新供应商[状态]同步数据到产品系统',
            //         'api_url' => $url_api,
            //         'post_content' => $data_post,
            //         'response_content' => $product_result,
            //         'status' => $product_result['code'] == 200 ? 1 : 0,
            //     ]
            // );

            $return = ['status' => 1, 'msg' => '同步成功'];
        } catch (Exception $e) {
            $return = ['status' => 0, 'msg' => $e->getMessage()];
        }
        return $return;
    }


    /** 根据供应商编码判定材料是否齐全 wangliang
     * @param $supplier_code 供应商编码
     * @return array
     */
    public function get_is_complete($supplier_code)
    {
        try {
            if (!$supplier_code) throw new Exception('供应商编码不可为空');

            $supplier_info = $this->get_supplier_info($supplier_code);
            if ($supplier_info['supplier_source'] != 1) throw new Exception('只有供应商来源为国内才有此选项');

            if (!$supplier_info['credit_code']) throw new Exception('供应商信用代码为空');


            $result = $this->Supplier_payment_info_model->get_payment_info_list($supplier_code);// 财务结算
            if (empty($result)) throw new Exception('支付方式为空');

            $image_list = $this->supplier_images_model->get_image_list($supplier_code);// 图片
            if (empty($image_list)) throw new Exception('图片为空');

            $payment_method = array_column($result, 'payment_method');
            $image_type = array_column($image_list, 'image_type');
            $image = array(
                1 => array('busine_licen' => '营业执照'),
                2 => array('busine_licen' => '营业执照', 'receipt_entrust_book' => '收款协议', 'idcard_front' => '身份证正面', 'idcard_back' => '身份证反面'),
                3 => array('busine_licen' => '营业执照', 'verify_book' => '纳税人证明', 'ticket_data' => '开票资料')
            );
            foreach ($image as $key => $value) {
                if (in_array($key, $payment_method)) {
                    foreach ($value as $k => $v) {
                        if (!in_array($k, $image_type)) throw new Exception($v . '不存在');
                    }
                }
            }
            $return = ['code' => 1, 'msg' => '资料齐全'];
            $result = $this->purchase_db->where('supplier_code', $supplier_code)->update($this->table_name, ['is_complete' => 1]);
            if (!$result) {
                $return = ['code' => 0, 'msg' => '数据更新失败'];
            }
        } catch (Exception $e) {
            $return = ['code' => 1, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /** 跨境宝批量修改验证 wangliang
     * @param $supplier_ids 供应商id 数组
     */
    public function check_cross($supplier_ids)
    {
        try {
            if (!is_array($supplier_ids) || empty($supplier_ids)) {
                throw new Exception('数据格式错误');
            }

            foreach ($supplier_ids as $v) {
                $supplier_info = $this->find_supplier_one($v, null, null, false);
                if (empty($supplier_info)) {
                    throw new Exception("供应商信息不存在[{$v}]");
                }

                if ($supplier_info['status'] != 1) {
                    throw new Exception("该供应商不是启用状态{$v}]");
                }


                if (!in_array($supplier_info['audit_status'], [SUPPLIER_REVIEW_PASSED, SUPPLIER_PURCHASE_REJECT, SUPPLIER_SUPPLIER_REJECT, SUPPLIER_FINANCE_REJECT])) {
                    throw new Exception("该供应商非审核通过状态[{$v}]");
                }

            }

            $return = ['code' => 1, 'msg' => '成功'];
        } catch (Exception $e) {
            $return = ['code' => 0, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /** 批量修改跨境宝
     * @param $supplier_ids 供应商id（数组格式）
     * @return array
     */
    public function update_supplier_cross($supplier_ids, $is_cross_border)
    {
        try {
            $check = $this->check_cross($supplier_ids);
            if ($check['code'] != 1) {
                throw new Exception($check['msg']);
            }
            $this->load->model('Supplier_audit_results_model', 'auditModel');
            $this->load->model('Supplier_update_log_model', 'updateLogModel');
            $this->purchase_db->trans_begin();
            foreach ($supplier_ids as $v) {
                $supplier_info = $this->find_supplier_one($v, null, null, false);
                if ($supplier_info['is_cross_border'] == $is_cross_border) {//修改前后一样的 不做修改
                    continue;
                }
                $supplier_code = $supplier_info['supplier_code'];
                //生成修改记录
                $change_data = [
                    'basis_data' => [
                        'is_cross_border' => $is_cross_border,
                    ]
                ];
                // 所有 变更的信息
                $all_change_data = [
                    'change_data' => $change_data,
                    'insert_data' => [],
                    'delete_data' => []
                ];

                $change_data_log = [
                    'basic' => [
                        'is_cross_border' => "修改前:" . getCrossBorder($supplier_info['is_cross_border']) . "修改后:" . getCrossBorder($is_cross_border),
                    ]
                ];
                //修改日志
                $all_change_data_log = [
                    'change_data_log' => $change_data_log,
                    'insert_data' => [],
                    'delete_data' => []
                ];


                // 生成一条 供应商数据变更待审核记录
                $insert_data = [
                    'supplier_code' => $supplier_code,
                    'action' => 'supplier/supplier/collect_update_supplier',
                    'message' => json_encode($all_change_data),
                    'audit_status' => SUPPLIER_WAITING_PURCHASE_REVIEW,
                    'create_user_id' => getActiveUserId(),
                    'create_user_name' => getActiveUserName(),
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                //保存修改日志
                operatorLogInsert(
                    [
                        'id' => $supplier_code,
                        'type' => 'supplier_update_log',
                        'content' => "供应商信息修改",
                        'detail' => json_encode(isset($all_change_data_log) ? $all_change_data_log : []),
                    ]
                );

                $result = $this->updateLogModel->insert_one($insert_data);
                if (empty($result)) {
                    throw new Exception("保存更新的数据失败");
                }

                $res = $this->auditModel->create_record($supplier_code);// 创建供应商审核记录
                if (!$res) {
                    throw new Exception('创建审核记录失败');
                }


            }
            //所有供应商数据保存成功 则提交事务
            if ($this->purchase_db->trans_status() !== false) {
                $this->purchase_db->trans_commit();
            } else {
                $this->purchase_db->trans_rollback();
                throw new Exception('数据提交失败');
            }

            $return = ['code' => 1, 'msg' => '修改成功'];
        } catch (Exception $e) {
            $return = ['code' => 0, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /** 根据供应商链接获取店铺ID
     * @param $shop_url 店铺地址
     * @return array
     */
    public function get_shop_id($shop_url)
    {
        $this->load->library('alibaba/AliSupplierApi');
        $results = $this->alisupplierapi->getSupplierShopInfo(null,$shop_url);
        $results = json_decode($results, true);
        if (isset($results['code']) && $results['code'] == 200 && isset($results['data']['result'])) {
            return ['code' => 1, 'data' => $results['data']['result'], 'msg' => '查询成功'];
        } else {
            return ['code' => 0, 'data' => [], 'msg' => isset($results['data']['errorMessage']) ? $results['data']['errorMessage'] : '请求错误' . json_encode($results)];
        }

    }


    /** 更新历史供应商店铺ID和旺旺号
     * @param $supplier_code 供应商编码
     * @return array
     */
    public function update_supplier_by_store_link($supplier_code)
    {

        try {
            if (!$supplier_code) {
                throw new Exception('供应商编码不能为空');
            }

            $info = $this->get_supplier_info($supplier_code, false);
            if (!$info) {
                throw new Exception('供应商不存在');
            }

            if (!isset($info['store_link']) || !$info['store_link']) {
                throw new Exception('供应商链接不存在');
            }

            $result = $this->get_shop_id($info['store_link']);
            if ($result['code'] != 1) {
                throw new Exception($result['msg']);
            }

            if (!isset($result['data']['loginId']) || !$result['data']['loginId']) {
                throw new Exception('未查询到数据');
            }

            $login_id = $result['data']['loginId'];
            if (!$info['shop_id']) {
                $update_supplier = $this->update_supplier(['id' => $info['id'], 'shop_id' => $login_id]);
                if (!$update_supplier) {
                    return ['code' => 0, 'msg' => '店铺ID更新失败'];
                }
            }

            $contact_table = $this->supplier_contact_model->table_nameName();
            $contract_info = $this->purchase_db->select('id')->where('supplier_code', $supplier_code)->where('want_want', '')->get($contact_table)->result_array();
            if (!$contract_info) {
                throw new Exception('联系人信息不存在');
            }

            foreach ($contract_info as $value) {
                $update_contract = $this->supplier_contact_model->update_supplier_contact(['id' => $value['id'], 'want_want' => $login_id, 'supplier_code' => $supplier_code], $supplier_code);
                if (!$update_contract) {
                    return ['code' => 0, 'msg' => '联系人旺旺更新失败'];
                }
            }

            return ['code' => 1, 'msg' => '更新成功'];
        } catch (Exception $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }

    }

    /**从老采购系统获取供应商合作金额（默认3个月）
     * @param $supplier_code 供应商编码数组
     * @param string $start_time 开始时间 2019-08-09 20:00:00
     * @param string $end_time 结束时间 2019-08-09 20:00:00
     * @return array|mixed
     */
    public function get_old_purchase_cooperation_price($supplier_code, $start_time = '', $end_time = '')
    {
        if (!is_array($supplier_code) || empty($supplier_code)) return [];

        $end_time = $end_time ?: date('Y-m-d H:i:s');
        $start_time = $start_time ?: date('Y-m-01 00:00:00', strtotime('-2 months'));
        $params = [
            'supplier_code' => $supplier_code,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ];
        $url = getConfigItemByName('api_config', 'old_purchase', 'supplier-get-purchase-num');
        $result = getCurlData($url, ['supplier' => json_encode($params)], 'post');
        $result = json_decode($result, true);

        return $result;
    }

    /** 获取近三个月供应商合作金额数据
     * @param $supplier_code
     * @param string $start
     * @param string $end
     * @return array
     */
    public function get_supplier_cooperation_amount($supplier_code, $start = '', $end = '')
    {
        if (empty($supplier_code) || !is_array($supplier_code)) {
            return [];
        }

        if (empty($start)) {
            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
        }
        //$cal_result[] = ['supplier_code'=>$supplier_code,'cooperation_amount'=>$total_amount,'payment_days_online_amount'=>$payment_days_online_amount,'payment_days_offline_amount'=>$payment_days_offline_amount,'no_payment_days_amount'=>$no_payment_days_amount];

        if (empty($end)) $end = date('Y-m-d H:i:s');
        $cooperation_amount = $this->purchase_db->select('sum(cooperation_amount) as cooperation_amount,sum(payment_days_online_amount) as payment_days_online_amount,sum(payment_days_offline_amount) as payment_days_offline_amount,sum(no_payment_days_amount) as no_payment_days_amount,supplier_code')
            ->where_in('supplier_code', $supplier_code)
            ->where('calculate_date >=', $start)
            ->where('calculate_date <=', $end)
            ->group_by('supplier_code')
            ->get('pur_supplier_cooperation_amount')
            ->result_array();
        $return = [];
        if (empty($cooperation_amount)) {
            return $return;
        }
        foreach ($cooperation_amount as $value) {
            $return[$value['supplier_code']] = ['cooperation_amount'=>$value['cooperation_amount'],'payment_days_online_amount'=>$value['payment_days_online_amount'],'payment_days_offline_amount'=>$value['payment_days_offline_amount'],'no_payment_days_amount'=>$value['no_payment_days_amount']];
        }

        return $return;
    }

    /** 生成禁用供应商时效记录
     * @param $supplier_code
     * @param string $remark
     * @return mixed
     */
    public function supplier_disable_record($supplier_code, $remark = '')
    {
        $this->load->model('supplier/Supplier_audit_results_model');
        $this->load->model('Supplier_audit_results_model', 'auditModel');
        $record = $this->Supplier_audit_results_model->get_waiting_audit_record($supplier_code);
        if (!$record) {
            $this->auditModel->create_record($supplier_code, 5);//禁用
        }

        $result = $this->auditModel->update_audit($supplier_code, SUPPLIER_AUDIT_RESULTS_STATUS_DISABLED, $remark);
        return $result;
    }


    /** 获取供应商详细变更信息（供应商从产品系统推到采购系统）
     * @param $old_supplier_info 修改前供应商信息
     * @param $new_supplier_info 修改前供应商信息
     * @param $operate_type 操作类型
     * @return array
     * @author wangliang
     */
    public function get_supplier_update_log_from_product($old_supplier_info, $new_supplier_info,$operate_type)
    {

        if (empty($new_supplier_info)) {
            $return = [
                'change_data' => [],
                'change_data_log' => [],
            ];
            return $return;
        }

        $change_data = [];// 修改的数据
        $insert_data = [];// 新增的数据（一对多关系的）
        $delete_data = [];// 删除的数据
        $change_data_log = [];// 修改日志
        $delete_payment_ids = [];//删除的支付方式
        $delete_contact_ids = [];//删除的联系方式
        $insert_payment_log =  [];//结算方式变更日志

        $old_supplier_basic = isset($old_supplier_info['supplier_basic']) ? $old_supplier_info['supplier_basic'] : [];
        $old_supplier_contact_list = isset($old_supplier_info['contact_list']) ? $old_supplier_info['contact_list'] : [];
        $old_supplier_images_list = isset($old_supplier_info['images_list']) ? $old_supplier_info['images_list'] : [];
        $old_supplier_payment_info = isset($old_supplier_info['supplier_payment_info']) ? $old_supplier_info['supplier_payment_info'] : [];
        $old_supplier_contact = arrayKeyToColumn($old_supplier_contact_list, 'contact_id');
        $old_supplier_images_list = array_column($old_supplier_images_list, 'image_url', 'image_type');

        $old_supplier_contact_purchase  = arrayKeyToColumn($old_supplier_contact_list, 'id');

        //新关联供应商
        $new_relation_supplier_list = !empty($new_supplier_info['relation_supplier'])?array_column($new_supplier_info['relation_supplier'],null,'relation_code'):[];
        $old_relation_supplier_list = !empty($old_supplier_info['relation_supplier'])?$old_supplier_info['relation_supplier']:[];




        // 供应商 变更的信息
        $new_supplier_basic = isset($new_supplier_info['supplier_basic']) ? $new_supplier_info['supplier_basic'] : [];
        $new_supplier_contact = isset($new_supplier_info['supplier_contact']) ? $new_supplier_info['supplier_contact'] : [];
        $new_supplier_contact = arrayKeyToColumn($new_supplier_contact, 'contact_id');
        $new_supplier_image = isset($new_supplier_info['supplier_image']) ? $new_supplier_info['supplier_image'] : [];
        $new_supplier_image = array_column($new_supplier_image, 'image_url', 'image_type');
        $new_supplier_payment_info = isset($new_supplier_info['supplier_payment_info']) ? $new_supplier_info['supplier_payment_info'] : [];

        $require_field = $this->getReqiredFieldsForLog();

        $all_fields = array_keys($require_field['reqFields']);
        $this->load->model('Supplier/Supplier_address_model', 'addressModel');
        $this->load->model('Supplier/Supplier_settlement_model', 'settlementModel');

        $settlement_code_name_map = $this->settlementModel->get_code_by_name_list();


        foreach ($all_fields as $change => $change_field) {

            if (isset($require_field['reqFields'][$change_field]) && $require_field['reqFields'][$change_field]) {

                /**基础信息（pur_supplier）更新开始**/
                if ($change_field == 'basic') {
                    unset($require_field['reqFields'][$change_field]['id']);



                    if (($new_supplier_basic['tap_date_str']!= $old_supplier_basic['tap_date_str'])||($new_supplier_basic['quota']!= $old_supplier_basic['quota'])||($new_supplier_basic['surplus_quota']!= $old_supplier_basic['surplus_quota'])) {


                        foreach ($new_supplier_payment_info as $is_tax => $item) {
                            foreach ($item as $purchase_type_id => $value) {
                                if (($old_supplier_payment_info[$is_tax][$purchase_type_id]['supplier_settlement'] == $new_supplier_payment_info[$is_tax][$purchase_type_id]['supplier_settlement'])&&($new_supplier_payment_info[$is_tax][$purchase_type_id]['supplier_settlement'] == 20)) {






                                    $payment_log = [
                                        'supplier_code'=>$old_supplier_basic['supplier_code'],
                                        'opr_user'=>getActiveUserName(),
                                        'opr_user_id'  =>getActiveUserId(),
                                        'opr_time'=>date('Y-m-d H:i:s'),
                                        'old_supplier_settlement'=>'1688账期'.($old_supplier_basic['tap_date_str']??''),
                                        'new_supplier_settlement'=>'1688账期'.($new_supplier_basic['tap_date_str']??''),
                                        'opr_type'=> $operate_type,
                                        'remark'  =>'',
                                        'purchase_type_id'=>$purchase_type_id,
                                        'is_tax'=>$is_tax,
                                        'content'=>json_encode(['quota'=>$old_supplier_basic['quota'],'tap_date_str'=>$old_supplier_basic['tap_date_str'],'surplus_quota'=>$old_supplier_basic['surplus_quota']]).','.json_encode(['quota'=>$new_supplier_basic['quota'],'tap_date_str'=>$new_supplier_basic['tap_date_str'],'surplus_quota'=>$new_supplier_basic['surplus_quota']])


                                    ];




                                    $insert_payment_log[] = $payment_log;//支付方式改变

                                }

                            }
                        }


                    }




                    foreach ($require_field['reqFields'][$change_field] as $field => $name) {
                        if (isset($old_supplier_basic[$field]) && isset($new_supplier_basic[$field]) && ($new_supplier_basic[$field] != $old_supplier_basic[$field])) {
                            $change_data['basis_data'][$field] = $new_supplier_basic[$field];
                            if (in_array($field, array('ship_province', 'ship_city', 'ship_area'))) {
                                if (!empty($old_supplier_basic[$field])) {
                                    $old_address = $this->addressModel->get_address_name_by_id($old_supplier_basic[$field]);
                                } else {
                                    $old_address = '';
                                }
                                if (!empty($new_supplier_basic[$field])) {
                                    $new_address = $this->addressModel->get_address_name_by_id($new_supplier_info[$field]);
                                } else {
                                    $new_address = '';
                                }
                                $change_data_log['basic'][$field] = '修改前:' . $old_address . ';修改后:' . $new_address;
                            } else {
                                $value = $new_supplier_basic[$field];
                                switch ($field) {// 需要转换成 中文
                                    case 'invoice':
                                        $before_v = supplier_ticket($old_supplier_basic[$field]);
                                        $current_v = supplier_ticket($value);
                                        break;
                                    case 'is_cross_border':
                                        $before_v = getCrossBorder($old_supplier_basic[$field]);
                                        $current_v = getCrossBorder($value);
                                        break;
                                    case 'supplier_type':
                                        $before_v = supplier_type($old_supplier_basic[$field]);
                                        $current_v = supplier_type($value);
                                        break;
                                    case 'settlement_type':
                                        $before_v = $this->settlementModel->get_settlement_one($old_supplier_basic[$field]);
                                        $current_v = $this->settlementModel->get_settlement_one($value);
                                        $before_v = isset($before_v['settlement_name']) ? $before_v['settlement_name'] : '';
                                        $current_v = isset($current_v['settlement_name']) ? $current_v['settlement_name'] : '';
                                        break;
                                    case 'supplier_settlement':
                                        $before_v = $this->settlementModel->get_settlement_one($old_supplier_basic[$field]);
                                        $current_v = $this->settlementModel->get_settlement_one($value);
                                        $before_v = isset($before_v['settlement_name']) ? $before_v['settlement_name'] : '';
                                        $current_v = isset($current_v['settlement_name']) ? $current_v['settlement_name'] : '';
                                        break;
                                    case 'is_complete':
                                        $before_v = getComplete($old_supplier_basic[$field]);
                                        $current_v = getComplete($value);
                                        break;
                                    case 'supplier_level':
                                        $before_v = getSupplierLevel($old_supplier_basic[$field]);
                                        $current_v = getSupplierLevel($value);
                                        break;
                                    case 'is_agent':
                                        $before_v = getIsAgent($old_supplier_basic[$field]);
                                        $current_v = getIsAgent($value);
                                        break;
                                    default :
                                        $before_v = $old_supplier_basic[$field];
                                        $current_v = $value;
                                }
                                $change_data_log['basic'][$field] = '修改前:' . $before_v . ';修改后:' . $current_v;
                            }
                        }

                    }
                }
                /**基础信息更新结束**/

                /**联系人信息（pur_supplier_contact）开始**/
                if ($change_field == 'contact') {
                    if (!empty($new_supplier_contact)) {

                        foreach ($new_supplier_contact as $key => $value) {
                            $contact_id = $value['contact_id'];
                            if (!isset($old_supplier_contact[$contact_id])) {
                                $insert_data['contact_data'][] = $value;
                                continue;
                            }

                            foreach ($value as $key2 => $value2) {
                                if (isset($old_supplier_contact[$contact_id][$key2]) && ($old_supplier_contact[$contact_id][$key2] != $value2)) {
                                    $change_data['contact_data'][$old_supplier_contact[$contact_id]['id']][$key2] = $value2;
                                    //修改日志
                                    $change_data_log['contact'][$old_supplier_contact[$contact_id]['id']][$key2] = '修改前:' . $old_supplier_contact[$contact_id][$key2] . ';修改后:' . $value2;
                                }
                            }
                        }

                    }

                    if (!empty($old_supplier_contact)) {
                        foreach ($old_supplier_contact as $key => $value) {
                            if (!isset($new_supplier_contact[$value['contact_id']])) {
                                $delete_contact_ids[] = $value['id'];
                            }
                        }
                    }

                    //采购系统新增的也删除

                    if (!empty($old_supplier_contact_purchase)) {
                        foreach ($old_supplier_contact_purchase as $old_contact_info) {
                            if (empty($old_contact_info['contact_id'])) {
                                $delete_contact_ids[] = $old_contact_info['id'];


                            }
                        }
                    }



                    $delete_contact_ids = array_unique($delete_contact_ids);

                    $delete_data['contact_data'] = $delete_contact_ids;



                    if ($delete_contact_ids) {
                        foreach ($delete_contact_ids as $pur_id) {

                            $delete_data_log['contact_data'][] = $old_supplier_contact_purchase[$pur_id];

                        }
                    }



                }
                /**联系人信息（pur_supplier_contact）结束**/

                /**图片信息(pur_supplier_image)开始**/
                if ($change_field == 'images') {
                    if (!empty($new_supplier_image)) {
                        foreach ($new_supplier_image as $image_type => $value) {
                           /* if (isset($old_supplier_images_list[$image_type]) and $old_supplier_images_list[$image_type] !== $value) {
                                $change_data['images_data'][$image_type] = $value;
                                //$change_data_log['images'][$image_type] = '修改前:'.$old_supplier_images_list[$image_type].';修改后:'.$value;
                                if (!is_array($old_supplier_images_list[$image_type])) {
                                    $change_data_log['images'][$image_type] = '修改前:<img src="' . $old_supplier_images_list[$image_type] . '" width="40" height="40">;修改后:<img src="' . $value . '" width="40" height="40">';
                                } else {
                                    $change_data_log['images'][$image_type] = '修改前:<img src="' . $old_supplier_images_list[$image_type][0] . '" width="40" height="40">;修改后:<img src="' . $value . '" width="40" height="40">';
                                }
                            }*/



                            if(!isset($old_supplier_images_list[$image_type])  or empty($old_supplier_images_list[$image_type])){//原图为空 则新增
                                if (empty($value)) continue;//不写入空数据
                                $insert_data['images_data'][$image_type] = $value;
                                continue;
                            }else{//更新
                                if($image_type != 'other_proof'){
                                    if(isset($old_supplier_images_list[$image_type]) and $old_supplier_images_list[$image_type] !== $value){
                                        $change_data['images_data'][$image_type] = $value;
                                        //$change_data_log['images'][$image_type] = '修改前:'.$old_supplier_images_list[$image_type].';修改后:'.$value;
                                        if(!is_array($old_supplier_images_list[$image_type])){
                                            $change_data_log['images'][$image_type] = '修改前:<img src="'.$old_supplier_images_list[$image_type].'" width="40" height="40">;修改后:<img src="'.$value.'" width="40" height="40">';
                                        }else{
                                            $change_data_log['images'][$image_type] = '修改前:<img src="'.$old_supplier_images_list[$image_type][0].'" width="40" height="40">;修改后:<img src="'.$value.'" width="40" height="40">';
                                        }
                                    }
                                }else{
                                    // 比较出 其他资料 新增/删除的图片
                                    if(strrpos($value,';') !== false){
                                        $new_img_list = explode(';',$value);
                                    }else{
                                        $new_img_list = [$value];
                                    }
                                    if(is_array($old_supplier_images_list[$image_type])){// 一种类型有多张图片的
                                        $old_img_list = $old_supplier_images_list[$image_type];
                                    }else{
                                        $old_img_list = [$old_supplier_images_list[$image_type]];
                                    }

                                    $add_url = array_diff($new_img_list,$old_img_list);
                                    $del_url = array_diff($old_img_list,$new_img_list);

                                   if ($add_url||$del_url) $change_data['images_data'][$image_type] = $value;
                                    $other_proof_log = '';
                                    if($add_url){
                                        if(is_string($add_url)) $add_url = [$add_url];
                                        $other_proof_log .= '<br/>新增图片:';
                                        foreach($add_url as $add_url_v){
                                            if(stripos($add_url_v,';') !== false){
                                                $images_value_list = explode(';',$add_url_v);
                                            }else{
                                                $images_value_list = [$add_url_v];
                                            }
                                            foreach($images_value_list as $images_value_tmp){
                                                $other_proof_log .= '<br/><img src="'.$images_value_tmp.'"  width="40" height="40">';
                                            }
                                        }
                                    }
                                    if($del_url){
                                        if(is_string($del_url)) $del_url = [$del_url];
                                        $other_proof_log .= '<br/>删除图片:';
                                        foreach($del_url as $del_url_v){
                                            $other_proof_log .= '<br/><img src="'.$del_url_v.'"  width="40" height="40">';
                                        }
                                    }
                                    if($other_proof_log)
                                        $change_data_log['images'][$image_type] = trim($other_proof_log,'<br/>');
                                }
                            }










                        }

                        /*$old_image_type = array_unique(array_keys($old_supplier_images_list));
                        $new_image_type = array_unique(array_keys($new_supplier_image));
                        $add_type = array_diff($new_image_type, $old_image_type);

                        $del_type = array_diff($old_image_type, $new_image_type);
                        $other_proof_log = '';


                        if ($add_type) {
                            $other_proof_log .= '<br/>新增图片:';
                            foreach ($add_type as $type) {
                                $add_url = $new_supplier_image[$type];
                                if (is_array($add_url)) $add_url = array_shift($add_url);
                                if (isset($require_field['reqFields'][$change_field][$type])) {
                                    $type_name = is_string($require_field['reqFields'][$change_field][$type]) ? $require_field['reqFields'][$change_field][$type] : '';
                                    $other_proof_log .= $type_name . ':<br/><img src="' . $add_url . '"  width="40" height="40">';
                                }

                            }
                        }
                        if ($del_type) {
                            $other_proof_log .= '<br/>删除图片:';
                            foreach ($del_type as $type) {
                                $del_url = $old_supplier_images_list[$type];
                                if (is_array($del_url)) $del_url = array_shift($del_url);
                                if (isset($require_field['reqFields'][$change_field][$type])) {
                                    $type_name = is_string($require_field['reqFields'][$change_field][$type]) ? $require_field['reqFields'][$change_field][$type] : '';
                                    $other_proof_log .= $type_name . ':<br/><img src="' . $del_url . '"  width="40" height="40">';
                                }
                            }
                        }

                        if ($other_proof_log)
                            $change_data_log['images'][$image_type] = trim($other_proof_log, '<br/>');*/
                    }
                }
                /**图片信息(pur_supplier_image)结束**/

                /** 财务结算(supplier_payment_info)开始 **/
                if ($change_field == 'payment_info') {
//                    pr($new_supplier_payment_info);exit;
                    if($new_supplier_payment_info){
                        foreach ($new_supplier_payment_info as $is_tax => $item){
                            foreach ($item as $purchase_type_id => $value){
                                $old_payment_info = $old_supplier_payment_info[$is_tax][$purchase_type_id]??[];

                                if (isset($now_invoice) && $now_invoice == INVOICE_TYPE_NONE){//是否开票=否, 不存在含税
                                    if ( $is_tax == PURCHASE_IS_DRAWBACK_Y ) {//跳过含税的
                                        continue;
                                    }
                                }

                                if (empty($old_payment_info)){
                                    //新增
                                    $insert_data['payment_data'][$is_tax][$purchase_type_id] = [
                                        'is_tax' => $is_tax,//是否含税
                                        'purchase_type_id' => $purchase_type_id,//业务线
                                        'supplier_code' => $value['supplier_code'],//供应商编码
                                        'settlement_type' => $value['settlement_type'],//结算方式(一级)
                                        'supplier_settlement' => $value['supplier_settlement'],//结算方式(二级)
                                        'payment_method' => (string)$value['payment_method'],//支付方式
                                        'account_name' => $value['account_name']??'',//账号名称 收款人姓名
                                        'account' => $value['account']??'',//银行账号 收款账号
                                        'currency' => $value['currency']??'',//币种
                                        'payment_platform_bank' => $value['payment_platform_bank']??'',//银行名称
                                        'bank_address' => $value['bank_address']??'',//开户行地址
                                        'swift_code' => $value['swift_code']??'',//swift代码
                                        'email' => $value['email']??'',//邮箱

                                        //线上支付宝
                                        'store_name' => $value['store_name']??'',//店铺名称

                                        //线下境内
                                        'payment_platform_bank' => $value['payment_platform_bank']??'',//开户行名称
                                        'payment_platform_branch' => $value['payment_platform_branch']??'',//开户行名称(支行)

                                        //线下境外
                                        'payment_platform' => $value['payment_platform']??'',//支付平台
                                        'phone_number' => $value['phone_number']??'',//收款人手机号
                                        'id_number' => $value['id_number']??'',//身份证号码



                                        //更新信息
                                        'update_time' => date('Y-m-d H:i:s'),
                                        'update_user_name' => getActiveUserName(),

                                        //创建信息
                                        'create_time' => date('Y-m-d H:i:s'),
                                        'create_user_name' => getActiveUserName(),

                                    ];
                                }else{
                                    //更新
                                    $change_data['payment_data'][$is_tax][$purchase_type_id] = [
                                        'id' => $old_payment_info['id'],
                                        'settlement_type' => $value['settlement_type'],//结算方式(一级)
                                        'supplier_settlement' => $value['supplier_settlement'],//结算方式(二级)
                                        'payment_method' => (string)$value['payment_method'],//支付方式
                                        'account_name' => $value['account_name']??'',//账号名称 收款人姓名
                                        'account' => $value['account']??'',//银行账号 收款账号
                                        'currency' => $value['currency']??'',//币种
                                        'payment_platform_bank' => $value['payment_platform_bank']??'',//银行名称
                                        'bank_address' => $value['bank_address']??'',//开户行地址
                                        'swift_code' => $value['swift_code']??'',//swift代码
                                        'email' => $value['email']??'',//邮箱

                                        //线上支付宝
                                        'store_name' => $value['store_name']??'',//店铺名称

                                        //线下境内
                                        'payment_platform_bank' => $value['payment_platform_bank']??'',//开户行名称
                                        'payment_platform_branch' => $value['payment_platform_branch']??'',//开户行名称(支行)

                                        //线下境外
                                        'payment_platform' => $value['payment_platform']??'',//支付平台
                                        'phone_number' => $value['phone_number']??'',//收款人手机号
                                        'id_number' => $value['id_number']??'',//身份证号码



                                        //更新信息
                                        'update_time' => date('Y-m-d H:i:s'),
                                        'update_user_name' => getActiveUserName(),
                                    ];

                                    foreach ($value as $k => $val){
                                        $old_val = $old_supplier_payment_info[$is_tax][$purchase_type_id][$k]??'';


                                        switch($k) {// 需要转换成 中文
                                            case 'settlement_type':
                                                $old_val = $settlement_code_name_map[$old_val]??'';
                                                $val = $settlement_code_name_map[$val]??'';
                                                break;
                                            case 'supplier_settlement':
                                                $old_val = $settlement_code_name_map[$old_val]??'';
                                                $val = $settlement_code_name_map[$val]??'';
                                                break;
                                            case 'payment_method':
                                                $old_val = getPayType($old_val);
                                                $val = getPayType($val);
                                                break;
                                            case 'payment_platform':
                                                $old_val = get_supplier_payment_platform($old_val);
                                                $val = get_supplier_payment_platform($val);
                                                break;
                                            case 'settlement_change_res':
                                                $old_val = getSettlementChangeRes($old_val);
                                                $val = getSettlementChangeRes($val);
                                                break;
                                            default :
                                                $old_val = $old_val;
                                                $val = $val;
                                        }





                                        if ($val != $old_val){
                                            $change_meg = sprintf('修改前:%s;修改后:%s',$old_val,$val);
                                            $change_data_log['payment_data'][$is_tax][$purchase_type_id][$k] = $change_meg;

                                            if ($k == 'supplier_settlement') {
                                          
                                                $payment_log = [
                                                    'supplier_code'=>$old_supplier_basic['supplier_code'],
                                                    'opr_user'=>getActiveUserName(),
                                                    'opr_user_id'  =>getActiveUserId(),
                                                    'opr_time'=>date('Y-m-d H:i:s'),
                                                    'old_supplier_settlement'=>$old_val,
                                                    'new_supplier_settlement'=>$val,
                                                    'opr_type'=> $operate_type,
                                                    'remark'  =>'',
                                                    'purchase_type_id'=>$purchase_type_id,
                                                    'is_tax'=>$is_tax




                                                ];



                                                if ($old_val == '1688账期') {
                                                    $payment_log['content'] = json_encode(['quota'=>$old_supplier_basic['quota'],'tap_date_str'=>$old_supplier_basic['tap_date_str'],'surplus_quota'=>$old_supplier_basic['surplus_quota']]);

                                                } elseif ($val == '1688账期') {
                                                    $payment_log['content'] = json_encode(['quota'=>$new_supplier_basic['quota'],'tap_date_str'=>$new_supplier_basic['tap_date_str'],'surplus_quota'=>$new_supplier_basic['surplus_quota']]);


                                                } else {
                                                    $payment_log['content'] = '';

                                                }


                                                $insert_payment_log[] = $payment_log;//支付方式改变






                                            }






                                        }







                                    }
                                }
                            }
                        }
                    }
                }
                /** 财务结算(supplier_payment_info)结束 **/
                if ($change_field == 'relation_supplier') {


                    $relation_supplier_codes = array_keys($new_relation_supplier_list);
                    $old_supplier_arr = array_keys($old_relation_supplier_list);



                    if ($new_relation_supplier_list){//更新供应商
                        foreach ($new_relation_supplier_list as $info) {
                            $insert =[];
                            $insert['supplier_code'] = $info['supplier_code'];
                            $insert['relation_code'] = $info['relation_code'];
                            $insert['supplier_type'] = $info['supplier_type'];
                            $insert['supplier_reason'] = $info['supplier_reason'];
                            $insert['supplier_type_remark'] = $info['supplier_type_remark']??'';
                            $insert['supplier_reason_remark'] = $info['supplier_reason_remark']??'';
                            $insert_data['relation_supplier'][] = $insert;

                        }


                    }

                    if (empty($relation_supplier_codes)&&!empty($old_supplier_arr)) {//删除供应商
                        $delete_data['relation_supplier'] =  $old_relation_supplier_list;

                    }







                    $add_info = array_diff($relation_supplier_codes,$old_supplier_arr);//添加的关联供应商
                    $del_info = array_diff($old_supplier_arr,$relation_supplier_codes);//删除的关联供应商
                    $update_info = array_intersect($relation_supplier_codes,$old_supplier_arr);//更新的供应商信息

                    if (!empty($add_info)) {
                        $add_str = implode(',',$add_info);
                        $change_data_log['supplier_relation'][] = '添加了关联供应商:'.$add_str;

                    }
                    if (!empty($del_info)) {
                        $del_str = implode(',',$del_info);
                        $change_data_log['supplier_relation'][] = '删除了关联供应商:'.$del_str;

                    }

                    if (!empty($update_info)) {

                            foreach ($update_info as $update_supplier) {

                                foreach ($require_field['reqFields'][$change_field] as $c_supplier_key=>$c_supplier_value) {
                                    if ($new_relation_supplier_list[$update_supplier][$c_supplier_key]!=$old_relation_supplier_list[$c_supplier_key]) {
                                        $old_v = $old_relation_supplier_list[$update_supplier][$c_supplier_key];
                                        $new_v = $new_relation_supplier_list[$update_supplier][$c_supplier_key];


                                        if ($c_supplier_key == 'supplier_type') {
                                            $old_v = getSupplierRelationType($old_v);
                                            $new_v = getSupplierRelationType($new_v);

                                        }

                                        if ($c_supplier_key == 'supplier_reason') {
                                            $old_v = getSupplierRelationReason($old_v);
                                            $new_v = getSupplierRelationReason($new_v);

                                        }

                                        $change_data_log['supplier_relation'][] ='关联供应商'.$update_supplier. '更改了关联供应商'.$c_supplier_value.'由'.$old_v.'更改成'.$new_v;


                                    }


                                }



                            }




                    }

                }
                //关联供应商结束

            }
        }
        // 所有 变更的信息
        $all_change_data = [
            'change_data' => $change_data,
            'insert_data' => $insert_data,
            'delete_data' => $delete_data
        ];

        //修改日志
        $all_change_data_log = [
            'change_data_log' => $change_data_log,
            'insert_data' => $insert_data,
            'delete_data' => isset($delete_data_log) ? $delete_data_log : []
        ];

        $return = [
            'change_data' => $all_change_data,
            'change_data_log' => $all_change_data_log,
            'insert_payment_log' =>$insert_payment_log
        ];


        return $return;
    }

    /**
     * http://192.168.71.156/web/#/86?page_id=2453
     * @param $params
     * @return array
     * @author Manson
     */
    public function get_register_by_java($params)
    {

        //入参
        $header = array('Content-Type: application/json');
        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSupplier-getSupplierInfoByCode');
        $access_token = getOASystemAccessToken();
        $request_url = $request_url . '?access_token=' . $access_token;

        $results = getCurlData($request_url, json_encode($params[0]), 'post', $header);
        $results = json_decode($results, true);
        pr($results);
        exit;
        if (isset($results['code']) && $results['code'] == 200 && isset($results['data']['content'])) {
            $data['total'] = $results['data']['totalElements'];
            $data['data'] = $results['data']['content'];
            $data['sortId'] = end($results['data']['content'])['id'];
            return $data;
        } else {
            $results = json_encode($results);
            log_message('error', sprintf('调用查询仓库库存接口异常,入参: %s,结果: %s', json_encode($params), $results));
            return [];
        }
    }

    /**
     * 检查信用代码是否存在 启用的
     * @author Manson
     */
    public function check_credit_code($credit_code, $id)
    {
        if (empty($credit_code) || empty($id)) {
            return false;
        }
        $row = $this->purchase_db->select('*')
            ->where('credit_code', $credit_code)
            ->where('search_status', 1)
            ->where('id !=', $id)
            ->get($this->table_name)->row_array();
        return empty($row) ? true : false;
    }



    /**
     * 包含类目
     * @author Manson
     */
    public function get_category_info($supplier_id){
        $result = $this->purchase_db->select('category_name')
            ->from('pur_prod_supplier_category')
            ->where('supplier_id',$supplier_id)
            ->where('is_del',0)
            ->get()->result_array();
        return empty($result)?[]:array_unique(array_column($result,'category_name'));

    }


    /**
     * 获取供应商结算方式
     * @param $supplier_code 供应商编码
     * @param $is_tax 是否退税
     * @param $purchase_type_id 业务线
     * @return array
     */
    public function new_get_settlement_data($supplier_code,$is_tax,$purchase_type_id){
        if (empty($supplier_code)) {
            return [];
        }
        if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $purchase_type_id = 2;
        }
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }
        if ($is_tax == 1){//含税的 不区分业务线
            $purchase_type_id = 0;
        }
        $where = ['supplier_code' => $supplier_code,'is_tax'=>$is_tax,'purchase_type_id'=>$purchase_type_id,'is_del'=>0];
        $result = $this->purchase_db->select('settlement_type,supplier_settlement')
            ->where($where)
            ->get('supplier_payment_info')
            ->row_array();
        $settlement = '';
        if(!empty($result)){
            $settlement = $result['supplier_settlement'];
        }
        return $settlement;
    }

    /**
     * 批量获取供应商支付方式
     * @author yefanli
     */
    public function get_suplier_payment_all($supplier_code)
    {
        $data = $this->purchase_db->select('supplier_code,is_tax,payment_method,purchase_type_id')
            ->where_in('supplier_code', $supplier_code)
            ->where(['is_del'=>0])
            ->get('supplier_payment_info')
            ->result_array();
        if($data && count($data) > 0)return $data;
        return [];
    }

    /**
     * 获取单个供应商支付方式
     * @author yefanli
     */
    public function get_suplier_payment_method_one($supplier, $supplier_code, $is_tax, $purchase_type_id)
    {
        if (empty($supplier_code) || empty($supplier))return null;

        //FBA 同国内
        if (in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]))$purchase_type_id = 1;
        if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $purchase_type_id = 2;
        }

        //含税的 不区分业务线
        if ($is_tax == 1)$purchase_type_id = 0;

        foreach ($supplier as $val){
            if($val['supplier_code'] == $supplier_code && $purchase_type_id == $val['purchase_type_id'] && $val['is_tax'] == $is_tax)return $val['payment_method'];
        }
        return null;
    }

    /**
     *获取供应商支付方式
     * @param $supplier_code
     * @param $is_tax
     * @param $purchase_type_id
     * @return array
     */
    public function new_get_suplier_payment_method($supplier_code,$is_tax,$purchase_type_id){
        if (empty($supplier_code)) {
            return '';
        }
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }
        if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $purchase_type_id = 2;
        }
        if ($is_tax == 1){//含税的 不区分业务线
            $purchase_type_id = 0;
        }
        $where = ['supplier_code' => $supplier_code,'is_tax'=>$is_tax,'purchase_type_id'=>$purchase_type_id,'is_del'=>0];
        $account_name = $this->purchase_db->select('payment_method')
            ->where($where)
            ->get('supplier_payment_info')
            ->row_array();
        $account_str = '';
        if(!empty($account_name)){
            $account_str = $account_name['payment_method'];
        }
        return $account_str;
    }

    public function get_supplier_payment_oversea($supplier_code,$purchase_type_id){

        if (empty($supplier_code)) {
            return '';
        }
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }
        if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $purchase_type_id = 2;
        }
        $where = ['supplier_code' => $supplier_code,'is_del'=>0];
        $account_name = $this->purchase_db->select('payment_method,is_tax')
            ->where($where)->where_in("purchase_type_id",[$purchase_type_id,0])
            ->get('supplier_payment_info')
            ->result_array();

        if(!empty($account_name)){

            $returnData = [];

            foreach($account_name as $key=>$value){

                if(!isset($returnData[$value['is_tax']])){

                    $returnData[$value['is_tax']] = array();
                }

                $returnData[$value['is_tax']][$value['payment_method']] = getPayType($value['payment_method']);
            }
            return $returnData;
        }
    }

    /**
     *获取供应商海外业务线的结算方式
     * @supplier_code  array  供应商CODE
     * @purchase_type_id int  业务线
     **/
    public function get_supplier_settlement_oversea($supplier_code,$purchase_type_id){

        if (empty($supplier_code)) {
            return '';
        }
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }
        if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $purchase_type_id = 2;
        }
        $where = ['supplier.supplier_code' => $supplier_code,'supplier.is_del'=>0];
        $account_name = $this->purchase_db->from("supplier_payment_info AS supplier")->select('settlement.settlement_name,supplier.supplier_settlement,supplier.is_tax')
            ->join("supplier_settlement AS settlement","supplier.supplier_settlement=settlement.settlement_code","LEFT")
            ->where($where)->where_in("purchase_type_id",[$purchase_type_id,0])
            ->where("settlement.settlement_status",1)->get()
            ->result_array();
        if(!empty($account_name)){

            $returnData = [];

            foreach($account_name as $key=>$value){

                if(!isset($returnData[$value['is_tax']])){

                    $returnData[$value['is_tax']] = array();
                }

               // $returnData[$value['is_tax']][$value['supplier_settlement']] =$value['settlement_name'];
                $returnData[$value['is_tax']][] = ['settlement_code'=>$value['supplier_settlement'],'settlement_name'=>$value['settlement_name']];
            }
            return $returnData;
        }
    }

    /**
     * key=>val
     * @param $supplier_code
     * @param $is_tax
     * @param $purchase_type_id
     * @return array
     */
    public function get_settlement_box($supplier_code,$is_tax,$purchase_type_id){
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }
        if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $purchase_type_id = 2;
        }
        if ($is_tax == 1){//含税的 不区分业务线
            $purchase_type_id = 0;
        }
        $settlement_info = [];
        $supplier_settlement = $this->new_get_settlement_data($supplier_code,$is_tax,$purchase_type_id);
        if (!empty($supplier_settlement)){
            $settlement_info = $this->purchase_db->select('settlement_code,settlement_name')->where('settlement_code', $supplier_settlement)->get('pur_supplier_settlement')->result_array();
        }
        return $settlement_info;
    }

    /**
     * 获取供应商是否开启对接门户系统
     * @params : supplierCodes    array   供应商CODE
     * @author:luxu
     * @time:2020/3/25
     * @return array 对接门户系统的供应商CODE
     **/
    public function getSupplierMessage($supplierCodes){
        if( empty($supplierCodes) ){
            return NULL;
        }

        $result = $this->purchase_db->from("supplier")->where_in("supplier_code",$supplierCodes)->where("is_gateway",SUGGEST_IS_GATEWAY_YES)->where('is_push_purchase',1)->select("supplier_code")->get()->result_array();
        return $result;
    }

    /**
     * 获取“是否支持跨境宝=否”的供应商店铺链接
     * @param int $limit
     * @param int $page
     * @return bool
     */
    public function get_supplier_store_link($limit = 20, $page = 1)
    {
        $len = $this->rediss->llenData('SUPPLIER_CROSS_BORDER');
        if ($len) {
            return false;
        }
        //查询出所有“是否支持跨境宝=否”的数据存入Redis
        while (1) {
            $offset = ($page - 1) * $limit;
            $data = $this->purchase_db->select('id,supplier_code,store_link')
                ->where('is_cross_border', 0)
                ->where('store_link <>','')
                ->limit($limit, $offset)
                ->get($this->table_name)->result_array();
            if (empty($data)) {
                break;
            }
            //只取供应商链接为1688平台的数据
            foreach ($data as $key =>$item){
                if(!strpos($item['store_link'],'1688.com')){
                    unset($data[$key]);
                }
            }

            $this->rediss->lpushData('SUPPLIER_CROSS_BORDER', $data);
            $page++;
        }
        return $this->rediss->llenData('SUPPLIER_CROSS_BORDER');
    }

    /**
     * 手动刷新,获取1688是否支持跨境宝
     * @param $supplier_ids
     * @return array
     */
    public function sync_1688_cross_border($supplier_ids)
    {
        $this->purchase_db->trans_begin();
        try {
            $check = $this->check_cross($supplier_ids);
            if ($check['code'] != 1) {
                throw new Exception($check['msg']);
            }

            $data = $this->purchase_db->select('id,supplier_code,store_link,is_cross_border')
                ->where('store_link <>', '')
                ->where_in('id', $supplier_ids)
                ->get($this->table_name)->result_array();
            if (empty($data)) {
                throw new Exception('没有符合刷新条件的数据');
            }
            $success =[];//刷新成功
            $failure =[];//刷新失败
            $no_change=[];//未发生变化
            foreach ($data as $item) {
                //根据‘供应商链接’获取供应商‘是否支持跨境宝’
                $_result = $this->get_shop_id(trim($item['store_link']));

                //查询结果失败，或1688返回‘是否支持跨境宝’结果与旧数据一样(不更新)
                if (!$_result['code'] or $_result['data']['kuaJingBao'] == $item['is_cross_border']) {
                    $no_change[]=$item['id'];
                    continue;
                }
                //根据‘供应商数据id’更新‘是否支持跨境宝’字段
                list($_status, $_message) = $this->update_supplier(['id' => $item['id'], 'is_cross_border' => 1]);
                if($_status){
                    $success[]=$item['id'];
                }else{
                    $failure[]=$item['id'];
                }
            }

            //所有供应商数据保存成功 则提交事务
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('数据提交失败');
            }
            $this->purchase_db->trans_commit();
            $return = ['code' => 1, 'msg' => '刷新成功[' . count($success) . ']条，失败[' . count($failure) . ']条，未发生变化[' . count($no_change) . ']条'];
        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $return = ['code' => 0, 'msg' => $e->getMessage()];
        }
        return $return;
    }



    /**
     * 供应商 启用 或 禁用
     * @param int $id 供应商ID
     * @param int $status 启用为1：（1.启用,2.禁用）
     * @param string $remark 备注
     * @return array
     */
    public function pre_disable($id, $status, $remark, $send = true)
    {
        $this->load->helper('api');
        $return = ['code' => false, 'message' => ''];
        $supplierInfo = $this->get_supplier_by_id($id, false);
        if (empty($supplierInfo)) {
            return ['code' => false, 'message' => '供应商状态不存在'];
        }
        $product_url = getConfigItemByName('api_config', 'product_system', 'updateSupplierStatus'); //获取推送供应商url
        $access_taken = getOASystemAccessToken();
        if (empty($product_url)) {
            return ['code' => false, 'message' => '产品系统api不存在'];
        }
        if (empty($access_taken)) {
            return ['code' => false, 'message' => '获取access_token值失败'];
        }

        if ($send) {
            //同步到产品系统
            $send_result = $this->send_supplier_status($supplierInfo['supplier_code'], $status, $remark);
        }

        $operator_id = getActiveUserId();
        $this->load->model('user/purchase_user_model');
        $user_info = $this->purchase_user_model->get_user_info_by_id($operator_id);
        $data_post = [
            'supplierCode' => $supplierInfo['supplier_code'],
            'status' => $status == PRE_DISABLE ? 12 : 3, //供应商状态 3已审核（启用） 4被驳回 7禁用 (对应产品系统)
            'createTime' => date('Y-m-d H:i:s'),
            'createUser' => isset($user_info['staff_code']) ? $user_info['staff_code'] : getActiveUserName(),
            'modifyReason' => $remark,
        ];
        $url_api = $product_url . "?access_token=" . $access_taken;
        $results = getCurlData($url_api, json_encode($data_post), 'post', array('Content-Type: application/json'));
        $product_result = json_decode($results, true);



        if ($status == PRE_DISABLE) {// 供应商禁用
            /*if(!in_array($supplierInfo['audit_status'],[SUPPLIER_PURCHASE_REJECT,SUPPLIER_SUPPLIER_REJECT,SUPPLIER_FINANCE_REJECT,SUPPLIER_REVIEW_PASSED])){
                $return['message'] = '供应链驳回|采购驳回|财务驳回 状态下才能禁用';
            }*/
            if (empty($return['message'])) {
                list($_result_status, $_message) = $this->update_supplier(['status' => PRE_DISABLE, 'search_status' => PRE_DISABLE, 'audit_status' => '7'], $id);
                if ($_result_status) {
                    operatorLogInsert(
                        [
                            'id' => $supplierInfo['supplier_code'],
                            'type' => 'supplier_update_log',
                            'content' => '供应商预禁用成功',
                            'detail' => '[预禁用]' . $remark,
                            'ext' => $supplierInfo['supplier_code'],
                            'operate_type' => SUPPLIER_PRE_DISABLE,
                        ]);
               /*     rejectNoteInsert(
                        [
                            'reject_type_id' => 4,
                            'link_id' => $id,
                            'link_code' => $supplierInfo['supplier_code'],
                            'reject_remark' => '[禁用]' . $remark
                        ]);*/


                    $return['code'] = true;
                    $return['message'] = '供应商预禁用成功';
                } else {
                    $return['message'] = '供应商预禁用失败';
                }

                $api_log = [
                    'record_number' => $supplierInfo['supplier_code'],
                    'api_url' => $url_api,
                    'record_type' => '推送供应商状态产品系统',
                    'post_content' => json_encode($data_post),
                    'response_content' => $results,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                $this->purchase_db->insert('api_request_log', $api_log);
                //$return['message'] = $product_result['msg'];
            }
        }

        return $return;

    }
    //根据供应商等级,操作告知函来判断等级
    public function cal_supplier_level($supplier_settlement,$attacheMapList)
    {


        $level = 0 ;
        $settlement_arr = [7,8,9 ,6 ,37 ,38, 20];//二级计算方式

        $is_period = 0;//是否账期或者1688账期
        $cooperation = 0;//是否存在合作告知函

        //X1 10 X2 11 X3 12 X4  20
        if (empty($supplier_settlement)||empty($attacheMapList)) {
            return $level;

        }

        foreach ($supplier_settlement as $key=>$value) {
            if (isset($value['supplierSettlement'])) {
                if (in_array($value['supplierSettlement'],$settlement_arr)) {
                    $is_period = 1;
                    break;

                }

            } elseif(isset($value['supplier_settlement'])) {

                if (in_array($value['supplier_settlement'],$settlement_arr)) {
                    $is_period = 1;
                    break;

                }


            }

        }

        foreach ($attacheMapList as $map=>$info) {

            if (!empty($info['cLetterUrl'])) {
                $cooperation = 1;
                break;

            }
            if (!empty($info['cooperation_letter'])) {
                $cooperation = 1;
                break;

            }

        }


        if($is_period&&$cooperation){
            $level = 10;

        }elseif($is_period&&!$cooperation){
            $level = 11;

        }elseif(!$is_period&&$cooperation){
            $level = 12;

        }elseif(!$is_period&&!$cooperation){
            $level = 13;

        }
        return $level;
    }

    //获取关联供应商的绑定sku数以及其他关联信息
    public function get_relation_supplier_info($supplier_code,$down_settlement)
    {

        //下拉列表合作状态
       $cooperation_status = getCooperationStatus();

        //下拉列表供应商来源
        $supplier_source   =  getSupplierSource();



        //初始化返回的值
        $data = [];
        $supplier_info = $this->get_supplier_info($supplier_code,false);
        //获取
        if (!empty($supplier_info)) {
            $data['supplier_name'] = $supplier_info['supplier_name'];
            $data['supplier_code'] = $supplier_info['supplier_code'];
            $data['cooperation_status'] = $cooperation_status[$supplier_info['status']];
            $data['supplier_source'] = $supplier_source[$supplier_info['supplier_source']];

        }
        //结算方式,支付方式

        // 获取支付方式, 结算方式
        $payment_info = $this->purchase_db->select("supplier_code,group_concat(payment_method) as payment_method,group_concat(supplier_settlement) as supplier_settlement")
            ->from('pur_supplier_payment_info')
            ->where('supplier_code', $supplier_code)
            ->where('is_del', 0)
            ->get()
            ->row_array();
        if (!empty($payment_info)) {
            if (!empty($payment_info['payment_method'])) {
                $payment_info['payment_method'] = explode(',',$payment_info['payment_method']);
                $payment_info['payment_method'] = array_unique($payment_info['payment_method']);
                $payment_info['payment_method'] = implode(',',$payment_info['payment_method']);

            }
            if (!empty($payment_info['supplier_settlement'])) {
                $payment_info['supplier_settlement'] = explode(',',$payment_info['supplier_settlement']);
                $payment_info['supplier_settlement'] = array_unique($payment_info['supplier_settlement']);
                $payment_info['supplier_settlement'] = implode(',',$payment_info['supplier_settlement']);

            }
            $data['payment_method']=$payment_info['payment_method'];
            $data['supplier_settlement']=$payment_info['supplier_settlement'];

        } else {
            $data['payment_method']='';
            $data['supplier_settlement']='';

        }


        //供应商结算方式
        if (isset($down_settlement)) {
            if($data['supplier_settlement'] and strpos($data['supplier_settlement'],',') !== false){
                $supplier_settlements = explode(',',$data['supplier_settlement']);
                $_supplier_settlement = [];
                foreach($supplier_settlements as $payment_method){
                    $_supplier_settlement[] = $down_settlement[$payment_method]??'';
                }
                $data['supplier_settlement'] = implode(',',$_supplier_settlement);
            }else{
                $_supplier_settlement = $down_settlement[$data['supplier_settlement']]??'';
                $data['supplier_settlement'] = $_supplier_settlement;


            }

//                    pr($down_settlement);exit;
//                    $item[$key]['supplier_settlement'] = isset($downSettlement[$info['supplier_settlement']]) ? $downSettlement[$info['supplier_settlement']] : $info['supplier_settlement'];
        }

        //支付方式
        if($data['payment_method'] and strpos($data['payment_method'],',') !== false){
            $payment_methods = explode(',',$data['payment_method']);
            $_paymentMethod = [];
            foreach($payment_methods as $payment_method){
                $_paymentMethod[] = getPayType($payment_method);
            }
            $data['payment_method'] = implode(',',$_paymentMethod);
        }else{
            $_paymentMethod = getPayType($data['payment_method']);
            if ($_paymentMethod) {
                $data['payment_method'] = $_paymentMethod;
            }
        }



        $where_arr=['supplier_code'=>$supplier_code,'product_type'=>1,'is_multi!='=>2];
        //获取sku_info
        $sku_info = $this->purchase_db->select("count(*) as num ,product_status")
            ->from('product')
            ->where($where_arr)
            ->group_by('product_status')
            ->get()
            ->result_array();

        $data['total_num'] = 0;//总数量
        $data['sale_num'] = 0;//在售数
        $data['stop_sale_num'] = 0;//停售数
        $data['other_num'] = 0;//其他

         if (!empty($sku_info)) {
             foreach ($sku_info as $info) {
                 $data['total_num']+=$info['num'];
                 if ($info['product_status'] == 4) {//在售数
                     $data['sale_num'] = $info['num'];//在售数
                 }
                 if ($info['product_status'] == 7) {
                     $data['stop_sale_num'] = $info['num'];//停售数

                 }
             }
             $data['other_num'] =  $data['total_num'] - $data['sale_num'] - $data['stop_sale_num'];

         }

         $start = date('Y-m-d 00:00:00', strtotime("-30 days"));
         $end = date('Y-m-d H:i:s');
        $cooperation_info = $this->purchase_db->select('sum(cooperation_amount) as cooperation_amount')
            ->where('supplier_code', $supplier_code)
            ->where('calculate_date >=', $start)
            ->where('calculate_date <', $end)
            ->get('pur_supplier_cooperation_amount')
            ->row_array();
         $data['cooperation_amount'] = $cooperation_info['cooperation_amount']??0;

         return $data;





    }

    //获取关联供应商信息
    public function get_relation_supplier_detail($supplier_code,$relation=true)
    {
        $list = [];
        $result = $this->purchase_db->select("relation.*,supplier.id as supplier_id")
            ->from('relation_supplier relation')
            ->join('supplier','supplier.supplier_code=relation.relation_code','left')
            ->where('relation.supplier_code', $supplier_code)
            ->get()
            ->result_array();

        if (!empty($result)&&$relation) {
            $down_settlement = $this->settlementModel->get_settlement();
            $down_settlement = array_column($down_settlement['list'],'settlement_name','settlement_code');
            foreach ($result as $key=>$value) {
                $value['relation_info']=$this->get_relation_supplier_info($value['relation_code'], $down_settlement);
                $list[$value['relation_code']]=$value;

            }



        } elseif(!empty($result)) {
            $list=array_column($result,null,'relation_code');

        }

        return empty($list)?null:$list;



    }

    //更新关联供应商信息

    public function update_relation_suppplier($supplier_code,$data,$type=1)
    {
        //获取关联供应商信息
        //删除历史的供应商防止重复

        $relation_info = $this->get_relation_supplier_detail($supplier_code,false);
        if (!empty($relation_info)) {
            $del_arr = [$supplier_code];
            //$res=$this->purchase_db->where('supplier_code',$supplier_code)->delete('relation_supplier');
            $relation_code_arr = array_keys($relation_info);
            $relation_list = $this->purchase_db->select('*')->from('relation_supplier')->where_in('supplier_code',$relation_code_arr)->get()->result_array();//其他关联供应商也应该删除
            if (!empty($relation_list)) {
                $del_arr = array_merge($del_arr,$relation_code_arr);
            }
           $res = $this->purchase_db->where_in('supplier_code', $del_arr)->delete('relation_supplier');


        }
        if ($type == 1) {//新增新的供应商,并新增对应的关联供应商

            $insert_relation_arr = [];

            if (!empty($data)) {
                foreach ($data as $insert) {
                    $temp = [];
                    $temp['supplier_code']=$insert['relation_code'];
                    $temp['relation_code']=$insert['supplier_code'];
                    $temp['supplier_type']=$insert['supplier_type'];
                    $temp['supplier_reason']=$insert['supplier_reason'];
                    $temp['supplier_type_remark']=$insert['supplier_type_remark'];
                    $temp['supplier_reason_remark']=$insert['supplier_reason_remark'];
                    if(isset($insert['other_images'])){
                        $temp['other_images']= is_string($insert['other_images'])?$insert['other_images']:json_encode($insert['other_images']);
                    }
                    $insert_relation_arr[]  =  $temp;

                }

                $insert_relation_arr = array_merge($insert_relation_arr,$data);
                $this->purchase_db->insert_batch('relation_supplier',$insert_relation_arr);
            }
        }
    }


    /**
     * 更新供应商账期信息
     * @param $seller_login_id
     * @param $supplier_code
     * @param array $supplier_info
     * @return array
     * @author Dean Jolon
     */
    public function update_supplier_quota($seller_login_id,$supplier_code,$supplier_info=[])
    {
        $this->load->library('alibaba/AliSupplierApi');
        $result = $this->alisupplierapi->getSupplierQuota(null, $seller_login_id);
        if (!is_json($result)) {
            return array(false, "1688 接口返回结果数据错误[非JSON]");
        }
        $result = json_decode($result, true);

        if (isset($result['errorCode']) and $result['errorCode'] !== null){
            return array(false, "数据获取错误，错误码[".$result['errorCode']."]");
        }elseif(isset($result['result']['errorCode']) and $result['result']['errorCode'] !== null) {
            return array(false, "数据获取错误，错误码[".$result['result']['errorCode']."]");
        }else{
            if (isset($result['result']['resultList']['accountPeriodList'][0])) {
                $tap_date_str = $result['result']['resultList']['accountPeriodList'][0]['tapDateStr'];//账期日期k
                $quota = $result['result']['resultList']['accountPeriodList'][0]['quota'];//授信额度值，单位（分）
                $sur_plus_quota = $result['result']['resultList']['accountPeriodList'][0]['surplusQuota']; //剩余可用额度

                $change_data_log = [];//记录日志
                if ($tap_date_str!=$supplier_info['tap_date_str']) {
                    $change_data_log['basic']['tap_date_str'] = '修改前:'.$supplier_info['tap_date_str'].';修改后:'.$tap_date_str;

                }
                if ($quota!=$supplier_info['quota']) {
                    $change_data_log['basic']['quota'] = '修改前:'.$supplier_info['quota'].';修改后:'.$quota;

                }
                if ($sur_plus_quota!=$supplier_info['surplus_quota']) {
                    $change_data_log['basic']['surplus_quota'] = '修改前:'.$supplier_info['surplus_quota'].';修改后:'.$sur_plus_quota;

                }

                if (!empty($change_data_log)) {
                    $all_change_data_log = [
                        'change_data_log' => $change_data_log,
                        'insert_data' => [],
                        'delete_data' => []
                    ];
                    //保存修改日志
                    operatorLogInsert(
                        [
                            'id' => $supplier_code,
                            'type' => 'supplier_update_log',
                            'content' => "供应商信息修改",
                            'detail' => json_encode(isset($all_change_data_log) ? $all_change_data_log : []),
                            'operate_type' => SUPPLIER_NORMAL_UPDATE,
                            'ext' => '[申请备注]:'
                        ]
                    );
                }

                $this->purchase_db->where(['supplier_code'=>$supplier_code])->update($this->table_name,['quota'=>$quota,'surplus_quota'=>$sur_plus_quota,'tap_date_str'=>$tap_date_str]);

                return array(true, "数据更新成功");
            } else {
                return array(false, "数据获取错误，不存在 result.resultList.accountPeriodList 参数");
            }
        }
    }



    /**
     * @desc 更新供应商账期信息
     * @return array()
     * @author Jolon
     * @Date 2020/10/24 16:44
     */
    public function update_supplier_quota_info()
    {
        $this->load->library('alibaba/AliSupplierApi');

        $update_supplier_quota_info_com = $this->rediss->getData('update_supplier_quota_info_com');
        if(0 < $update_supplier_quota_info_com and time() - $update_supplier_quota_info_com < 7200){
            echo '两小时内不用重复刷新';exit;
        }

        // 先把所有线上账期结算方式的供应商查出来存储到REDIS里面
        $row_count = $this->rediss->set_scard('update_supplier_quota_info');
        if($row_count <=0 ){
            $sql = "SELECT A.`supplier_code`,B.`supplier_name`,B.store_link,B.shop_id
                FROM `pur_supplier_payment_info` AS A
                LEFT JOIN `pur_supplier` AS B ON A.`supplier_code`=B.`supplier_code`
                WHERE B.shop_id !='' AND A.supplier_settlement=20
                GROUP BY A.`supplier_code`";
            $list = $this->purchase_db->query($sql)->result_array();
            foreach ($list as $value) {
                $this->rediss->set_sadd('update_supplier_quota_info', json_encode($value));
            }
        }

        $num = 0;
        do{
            $num ++;
            $data = $this->rediss->set_spop('update_supplier_quota_info');
            if(empty($data) or empty($data[0])){
                $this->rediss->setData('update_supplier_quota_info_com',time());
                break;
            }

            $data           = json_decode($data[0],true);
            $sellerLoginId  = $data['shop_id'];
            $supplier_code  = $data['supplier_code'];
            $message        = $supplier_code."--".$sellerLoginId."--";
            $resultJson     = $this->alisupplierapi->getSupplierQuota(null, $sellerLoginId);

            try{
                if (!is_json($resultJson)) {
                    throw new Exception('1688 接口返回结果数据错误');
                }

                $result = json_decode($resultJson, true);
                if ((isset($result['errorCode']) and $result['errorCode'] !== null)
                    or (isset($result['result']['errorCode']) and $result['result']['errorCode'] !== null)) {

                    $message = isset($result['errorMessage']) ? $result['errorMessage'] : '';
                    $message .= isset($result['result']['errorMsg']) ? $result['result']['errorMsg'] : '';

                    throw new Exception($message);

                } else {
                    if (isset($result['result']['resultList']['accountPeriodList'][0])) {
                        $return_data = [];
                        $return_data['tap_date_str_sync'] = $result['result']['resultList']['accountPeriodList'][0]['tapDateStr'];//账期日期

                        $this->purchase_db->where('supplier_code',$supplier_code)->update('pur_supplier',$return_data);
                    } else {
                        throw new Exception('数据项accountPeriodList缺失');
                    }
                }


                //写入API请求记录表
                apiRequestLogInsert(
                    [
                        'record_number' => $supplier_code,
                        'record_type' => 'UPDATE_SUPPLIER_QUOTA_INFO',
                        'api_url' => '/alisupplierapi/getSupplierQuota',
                        'post_content' => $message,
                        'response_content' => $resultJson,
                        'status' => 1
                    ],
                    'api_request_ali_log'
                );


            }catch (Exception $e){

                //写入API请求记录表
                apiRequestLogInsert(
                    [
                        'record_number' => $supplier_code,
                        'record_type' => 'UPDATE_SUPPLIER_QUOTA_INFO',
                        'api_url' => '/alisupplierapi/getSupplierQuota',
                        'post_content' => $message.$e->getMessage(),
                        'response_content' => $resultJson,
                        'status' => 0
                    ],
                    'api_request_ali_log'
                );

            }


            if($num >= 100){// 每次执行 200 个
                break;
            }

        }while(true);

        echo '--end';exit;
    }




    /**
     * 供应商 加入黑名单
     * @param int $id 供应商ID
     * @param int $status 启用为1：（1.启用,2.禁用）
     * @param string $remark 备注
     * @return array
     */
    public function supplier_opr_black_list($params, $send = true)
    {
        $status = IS_BLACKLIST;
        $this->load->helper('api');
        $return = ['code' => false, 'message' => ''];
        $supplierInfo = $this->find_supplier_one(0,$params['supplier_code'] );
        if (empty($supplierInfo)) {
            return ['code' => false, 'message' => '供应商状态不存在'];
        }
        $product_url = getConfigItemByName('api_config', 'product_system', 'updateSupplierStatus'); //获取推送供应商url
        $access_taken = getOASystemAccessToken();
        if (empty($product_url)) {
            return ['code' => false, 'message' => '产品系统api不存在'];
        }
        if (empty($access_taken)) {
            return ['code' => false, 'message' => '获取access_token值失败'];
        }

        if ($send) {
            //同步到产品系统
            $send_result = $this->send_supplier_status($supplierInfo['supplier_code'], $status, $params['reason']);
        }

        $operator_id = getActiveUserId();
        $this->load->model('user/purchase_user_model');
        $user_info = $this->purchase_user_model->get_user_info_by_id($operator_id);
        $data_post = [
            'supplierCode' => $supplierInfo['supplier_code'],
            'status' => $status == IS_BLACKLIST ? 15 : 3, //供应商状态 3已审核（启用） 4被驳回 7禁用 (对应产品系统)
            'createTime' => date('Y-m-d H:i:s'),
            'createUser' => isset($user_info['staff_code']) ? $user_info['staff_code'] : getActiveUserName(),
            'modifyReason' => $params['reason'],
        ];
        $url_api = $product_url . "?access_token=" . $access_taken;
        $results = getCurlData($url_api, json_encode($data_post), 'post', array('Content-Type: application/json'));
        $product_result = json_decode($results, true);


            /*if(!in_array($supplierInfo['audit_status'],[SUPPLIER_PURCHASE_REJECT,SUPPLIER_SUPPLIER_REJECT,SUPPLIER_FINANCE_REJECT,SUPPLIER_REVIEW_PASSED])){
                $return['message'] = '供应链驳回|采购驳回|财务驳回 状态下才能禁用';
            }*/
            if (empty($return['message'])) {
                list($_result_status, $_message) = $this->update_supplier(['status' => $status, 'search_status' => IS_BLACKLIST, 'audit_status' => '7'], $supplierInfo['id']);
                if ($_result_status) {





                    operatorLogInsert(
                        [
                            'id' => $supplierInfo['supplier_code'],
                            'type' => 'supplier_update_log',
                            'content' => '供应商加入黑名单成功',
                            'detail' => '[加入黑名单]' . $params['reason'],
                            'ext' => $supplierInfo['supplier_code'],
                            'operate_type' => SUPPLIER_IS_BLACKLIST,
                        ]);
                    rejectNoteInsert(
                        [
                            'reject_type_id' => 4,
                            'link_id' => $supplierInfo['id'],
                            'link_code' => $supplierInfo['supplier_code'],
                            'reject_remark' => '[加入黑名单]' . $params['reason']
                        ]);

                    //供应商禁用需终结之前生成的审核记录
                    $this->supplier_blacklist_record($supplierInfo['supplier_code'], $params['reason']);

                    $return['code'] = true;
                    $return['message'] = '供应商加入黑名单成功';

                    $black_list_one = $this->purchase_db->select('*')->from('supplier_blacklist')->where(['supplier_code'=>$supplierInfo['supplier_code']])->get()->row_array();

                    if (!empty($black_list_one)) {
                        $update_data = $params;
                        $update_data['opr_user'] = getActiveUserName();
                        $update_data['opr_user_id'] = getActiveUserId();
                        $update_data['opr_time'] =date('Y-m-d H:i:s');
                        unset($update_data['uid']);
                        $this->purchase_db->where('supplier_code',$supplierInfo['supplier_code'])->update('supplier_blacklist',$update_data);

                    } else {
                        $add_data = $params;
                        $add_data['opr_user'] = getActiveUserName();
                        $add_data['opr_user_id'] = getActiveUserId();
                        $add_data['opr_time'] =date('Y-m-d H:i:s');
                        unset($add_data['uid']);

                        $this->purchase_db->insert('supplier_blacklist',$add_data);


                    }




                } else {
                    $return['message'] = '供应商加入黑名单失败';
                }

                $api_log = [
                    'record_number' => $supplierInfo['supplier_code'],
                    'api_url' => $url_api,
                    'record_type' => '推送供应商状态产品系统',
                    'post_content' => json_encode($data_post),
                    'response_content' => $results,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                $this->purchase_db->insert('api_request_log', $api_log);
                //$return['message'] = $product_result['msg'];
            }


        return $return;

    }


    /** 生成加入黑名单供应商时效记录
     * @param $supplier_code
     * @param string $remark
     * @return mixed
     */
    public function supplier_blacklist_record($supplier_code, $remark = '')
    {
        $this->load->model('supplier/Supplier_audit_results_model');
        $this->load->model('Supplier_audit_results_model', 'auditModel');
        $record = $this->Supplier_audit_results_model->get_waiting_audit_record($supplier_code);
        if (!$record) {
            $this->auditModel->create_record($supplier_code, 7);//加入黑名单
        }

        $result = $this->auditModel->update_audit($supplier_code, SUPPLIER_AUDIT_RESULTS_STATUS_BLACKLIST, $remark);
        return $result;
    }


    /**
     * @desc 获取供应商列表(分页)
     * @return array()
     **@author Jackson
     * @Date 2019-01-21 17:01:00
     */
    public function black_list($params = [])
    {
        $supplier_level_list = getSupplierLevel();

        $params = $this->table_query_filter($params);

        $query_builder = $this->purchase_db;

        //查询字段
        $fields = 'A.create_time,A.is_push_purchase,A.is_gateway,A.id,A.supplier_code,A.supplier_name,A.supplier_settlement,A.payment_method,A.supplier_level,A.supplier_source,A.create_user_name,A.sku_num,A.store_link,A.status,A.audit_status,A.search_status,
                A.is_complete,A.is_cross_border,A.is_diversion_status,C.linelist_cn_name';

        $query_builder->select($fields)
            ->from($this->table_name . ' AS A')
            ->join($this->supplier_product_line_table_name . ' AS B', "A.supplier_code=B.supplier_code AND B.status=1", 'LEFT')
            ->join($this->product_line_table_name . " AS C", "B.first_product_line=C.product_line_id", 'LEFT')
            ->join("pur_supplier_payment_info AS D", "A.supplier_code=D.supplier_code AND D.is_del=0", 'LEFT')
            ->join($this->pur_supplier_cooperation_amount . " AS E", "A.supplier_code=E.supplier_code", 'LEFT');
$query_builder->where('A.status=',IS_BLACKLIST);        if (isset($params['payment_method'])) {
            $query_builder->where('D.payment_method', $params['payment_method']);
        }






        if( isset($params['reportloss_start']) && isset($params['reportloss_end'])){

            $query_builder->where(" A.supplier_code IN (
                        SELECT
                            supplier_code
                        FROM
                            pur_purchase_order AS orders
                        LEFT JOIN `pur_purchase_order_reportloss` AS `reportloss` ON `reportloss`.`pur_number` = `orders`.`purchase_number`
                        WHERE reportloss.status=4
                        GROUP BY
                            orders.supplier_code
                        HAVING
                            SUM(loss_totalprice) >= {$params['reportloss_start']}  AND SUM(loss_totalprice)<={$params['reportloss_end']}
                    )"
            );
        }

        if (isset($params['buyer'])) {
            if ($params['buyer']==0) {
                $query_builder->where("A.supplier_code NOT IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_type=1 GROUP BY supplier_code)");

            } else {
                $query_builder->where("A.supplier_code IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_id='{$params['buyer']}' AND pur_{$this->buyer_name}.status='1' GROUP BY supplier_code)");

            }

        }
        if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
            if (is_array($params['supplier_code'])) {
                $query_builder->where_in('A.supplier_code', $params['supplier_code']);
            } else {
                $query_builder->where('A.supplier_code', $params['supplier_code']);
            }
        }

        if(isset($params['is_gateway']) && $params['is_gateway'] != NULL){

            if( $params['is_gateway'] == 1){
                $query_builder->where_in('A.is_gateway',SUGGEST_IS_GATEWAY_YES)->where('A.is_push_purchase',1);
            }else{
                $query_builder->where('(A.is_gateway',SUGGEST_IS_GATEWAY_NO)->or_where('A.is_push_purchase=2)');
            }
        }
        if (isset($params['create_user_id'])) {
            $query_builder->where('A.create_user_id', $params['create_user_id']);
        }
        if (isset($params['supplier_settlement'])) {

            if(is_array($params['supplier_settlement'])) {
                $query_builder->where_in('D.supplier_settlement', $params['supplier_settlement']);
            }else {
                $query_builder->where('D.supplier_settlement', $params['supplier_settlement']);
            }
        }
        if (isset($params['supplier_level'])&&!empty($params['supplier_level'])) {
            $query_builder->where_in('A.supplier_level', $params['supplier_level']);
        }
        if (isset($params['is_cross_border'])) {
            $query_builder->where('A.is_cross_border', $params['is_cross_border']);
        }
        if (isset($params['status'])) {
            //$query_builder->where('A.status',$params['status']);
            if(is_array($params['status'])){
                $query_builder->where_in('A.audit_status', $params['status']);
            }else{
                $query_builder->where('A.audit_status', $params['status']);
            }
        }
        if (isset($params['first_product_line'])) {
            $query_builder->where_in('B.first_product_line', $params['first_product_line']);
        }

        if (isset($params['cooperation_status'])) {
            $query_builder->where('A.status', $params['cooperation_status']);
        }

        if (isset($params['supplier_source'])) {
            $query_builder->where('A.supplier_source', $params['supplier_source']);
        }


        if (isset($params['is_diversion_status'])) {
            $query_builder->where('A.is_diversion_status', $params['is_diversion_status']);
        }

        if (isset($params['is_complete']) && is_numeric($params['is_complete'])) {
            $query_builder->where('A.is_complete', $params['is_complete']);
        }
        //有效sku数量
        if (isset($params['min_sku_num'])) {
            $query_builder->where('A.sku_num >=', intval($params['min_sku_num']));
        }
        if (isset($params['max_sku_num'])) {
            $query_builder->where('A.sku_num <=', intval($params['max_sku_num']));
        }

        if (!empty($params['cooperation_letter'])) {
            $query_builder->join("pur_supplier_images AS images", "A.supplier_code=images.supplier_code AND images.image_type='cooperation_letter'", 'LEFT');

            if ($params['cooperation_letter'] == 1) {
                $query_builder->where('images.image_url IS NOT NULL');

            } else {
                $query_builder->where('images.image_url IS  NULL');

            }


        }
        //关联供应商
        if (!empty($params['relation_codes'])) {
            $query_builder->join("pur_relation_supplier AS relation", "A.supplier_code=relation.supplier_code ", 'LEFT');
            $query_builder->where_in('relation.relation_code',$params['relation_codes']);


        }

        if (!empty($params['create_time_start'])) {
            $query_builder->where('A.create_time >=', $params['create_time_start']);


        }

        if (!empty($params['create_time_end'])) {
            $query_builder->where('A.create_time <=', $params['create_time_end']);


        }


        // 3.分页参数
        $pageSize = query_limit_range(isset($params['limit']) ? $params['limit'] : 0);
        $page = !isset($params['offset']) || intval($params['offset']) <= 0 ? 1 : intval($params['offset']);
        $offset = ($page - 1) * $pageSize;
        // 三个月合作金额搜索条件
        if ((isset($params['min_nineth_data']) && strlen($params['min_nineth_data']) > 0) || (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0)) {
            $params['min_nineth_data'] = $params['min_nineth_data'] ?? 0;
            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
            $end = date('Y-m-d H:i:s');
            $nineth_sql = "A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING (sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) >= {$params['min_nineth_data']})";
//            $query_builder->where(" D.calculate_date >= '{$start}' AND D.calculate_date  <= '{$end}' GROUP BY D.supplier_code HAVING sum(D.cooperation_amount) >= {$params['min_nineth_data']}");
        }
        if (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0) {
//            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
//            $end = date('Y-m-d H:i:s');
//            $query_builder->where("A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}");
            $nineth_sql .= " AND sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}";
        }
        if (isset($nineth_sql)) {
            $query_builder->where($nineth_sql . ')');
        }


           //一个月合作金额
        if ((isset($params['min_thirty_data']) && strlen($params['min_thirty_data']) > 0) || (isset($params['max_thirty_data']) && strlen($params['max_thirty_data']) > 0)) {
            $params['min_thirty_data'] = $params['min_thirty_data'] ?? 0;
            $start = date('Y-m-d 00:00:00', strtotime("-30 days"));
            $end = date('Y-m-d H:i:s');
            $thirty_sql = "A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING (sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) >= {$params['min_thirty_data']})";
//            $query_builder->where(" D.calculate_date >= '{$start}' AND D.calculate_date  <= '{$end}' GROUP BY D.supplier_code HAVING sum(D.cooperation_amount) >= {$params['min_nineth_data']}");
        }
        if (isset($params['max_thirty_data']) && strlen($params['max_thirty_data']) > 0) {
//            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
//            $end = date('Y-m-d H:i:s');
//            $query_builder->where("A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}");
            $thirty_sql .= " AND sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_thirty_data']}";
        }
        if (isset($thirty_sql)) {
            $query_builder->where($thirty_sql . ')');
        }
        $query_builder->order_by('A.id DESC')->group_by('A.id');
        $count_db = clone($query_builder);
        $count_num = $count_db->count_all_results();


        $list = $query_builder->offset($offset)->limit($pageSize)->get()->result_array();



        if ($list) {
            $supplier_codes = array_column($list, 'supplier_code');
            // 获取采购员信息
            $buyer_lists = $this->purchase_db->select("supplier_code,buyer_type,buyer_name as buyer")
                ->from($this->buyer_name)
                ->where('status', 1)
                ->where_in('supplier_code', $supplier_codes)
                ->order_by('buyer_type asc')
                ->get()
                ->result_array();
            $buyer_lists = arrayKeyToColumnMulti($buyer_lists, 'supplier_code');

            $supplier_codes = array_column($list, 'supplier_code');

            // 获取支付方式, 结算方式
            $payment_info = $this->purchase_db->select("supplier_code,group_concat(payment_method) as payment_method,group_concat(supplier_settlement) as supplier_settlement")
                ->from('pur_supplier_payment_info')
                ->where_in('supplier_code', $supplier_codes)
                ->where('is_del', 0)
                ->group_by('supplier_code')
                ->get()
                ->result_array();
            $payment_info = array_column($payment_info, NULL, 'supplier_code');
            foreach ($payment_info as $key => &$item){
                if (!empty($item['payment_method'])){
                    $item['payment_method'] = explode(',',$item['payment_method']);
                    $item['payment_method'] = array_unique($item['payment_method']);
                    $item['payment_method'] = implode(',',$item['payment_method']);
                }
                if (!empty($item['supplier_settlement'])){
                    $item['supplier_settlement'] = explode(',',$item['supplier_settlement']);
                    $item['supplier_settlement'] = array_unique($item['supplier_settlement']);
                    $item['supplier_settlement'] = implode(',',$item['supplier_settlement']);
                }
            }
            //三个月合作金额
            $cooperation_amount = $this->get_supplier_cooperation_amount($supplier_codes);

            //一个月合作金额

            $cooperation_amount_one_month = $this->get_supplier_cooperation_amount($supplier_codes,date('Y-m-d 00:00:00', strtotime("-30 days")));

            $supplier_reportData = [];
            if( !empty($list) ){

                $supplier_codes = array_filter(array_column( $list,"supplier_code"));

                $supplier_reportData_query = $this->purchase_db->select(" SUM(loss_totalprice) AS loss_totalprice,orders.supplier_code")->from("purchase_order AS orders")->join(" pur_purchase_order_reportloss AS reportloss","reportloss.pur_number = orders.purchase_number");
                $supplier_reportData = $supplier_reportData_query->where("reportloss.status",4)->where_in("orders.supplier_code",$supplier_codes)->group_by("orders.supplier_code")->get()->result_array();
                if( !empty($supplier_reportData)){
                    $supplier_reportData = array_column( $supplier_reportData,NULL,"supplier_code");
                }


            }
            foreach ($list as $key => $value) {

                $now_buyer = isset($buyer_lists[$value['supplier_code']]) ? $buyer_lists[$value['supplier_code']] : [];
                $list[$key]['buyer'] = array_column($now_buyer, 'buyer', 'buyer_type');

                if(  isset($supplier_reportData[$value['supplier_code']]) && $supplier_reportData[$value['supplier_code']]['loss_totalprice'] != NULL ){

                    $list[$key]['loss_money'] = $supplier_reportData[$value['supplier_code']]['loss_totalprice'];
                }else{

                    $list[$key]['loss_money'] = 0;
                }
//                $list[$key]['supplier_level']= !empty($supplier_level_list[$list[$key]['supplier_level']])?$supplier_level_list[$list[$key]['supplier_level']]:$list[$key]['supplier_level'];
                if(!empty($list[$key]['supplier_level'])) {
                    if (!empty($supplier_level_list[$list[$key]['supplier_level']])) {
                        $list[$key]['supplier_level'] = $supplier_level_list[$list[$key]['supplier_level']];
                    }else{
                        $list[$key]['supplier_level'];
                    }
                }else{
                    $list[$key]['supplier_level'] = '';
                }

                $list[$key]['payment_method'] = isset($payment_info[$value['supplier_code']]) ? $payment_info[$value['supplier_code']]['payment_method'] : '';
                $list[$key]['supplier_settlement'] = isset($payment_info[$value['supplier_code']]) ? $payment_info[$value['supplier_code']]['supplier_settlement'] : '';
                //获取最新一条审核备注
                $sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log AS a
	WHERE record_number = '{$value['supplier_code']}' AND record_type
	= 'SUPPLIER_UPDATE_LOG' AND operate_route = 'supplier/Supplier/supplier_review' order by id desc limit 1";

                $detail_list = $this->purchase_db->query($sql)->row_array();
//                $detail_list = array_column($detail_list, 'content_detail', 'supplier_code');
                $detail_list[$value['supplier_code']] = $detail_list['content_detail'];

                $list[$key]['contract_notice'] = isset($detail_list[$value['supplier_code']]) ? $detail_list[$value['supplier_code']] : '';

                if( $value['is_gateway'] == SUGGEST_IS_GATEWAY_YES && $value['is_push_purchase'] == 1){

                    $list[$key]['is_gateway_ch'] = '是';
                }else{
                    $list[$key]['is_gateway_ch'] = '否';
                }

                if ($value['status'] == IS_BLACKLIST) {//如果供应商处于禁用状态 显示禁用备注

                    $query_sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log WHERE record_number = '{$value['supplier_code']}' AND record_type
	in ('SUPPLIER_UPDATE_LOG','PUR_SUPPLIER') AND operate_route = 'supplier/Supplier/supplier_opr_black_list' AND content = '供应商加入黑名单成功' order by id desc limit 1";
                    $fobidden_remark = $this->purchase_db->query($query_sql)->row_array();
//                    $fobidden_remark_list = array_column($fobidden_remark, 'content_detail', 'supplier_code');
                    $fobidden_remark_list[$value['supplier_code']] = $fobidden_remark['content_detail'];

                    $current_forbidden_remark = isset($fobidden_remark_list[$value['supplier_code']]) ? mb_substr($fobidden_remark_list[$value['supplier_code']], 4) : '';
                    $current_forbidden_remark ? $list[$key]['contract_notice'] .= PHP_EOL . '加入黑名单原因:' . $current_forbidden_remark : '';
                }

                $list[$key]['supplier_source_name'] = getSupplierSource($value['supplier_source']);
            /*    if (isset($apply_remark_list[$value['supplier_code']]) && strpos($apply_remark_list[$value['supplier_code']], '申请备注')) {
                    $list[$key]['contract_notice'] = $apply_remark_list[$value['supplier_code']];
                }*/
//
//                $settlement = $this->get_settlement_name($value['supplier_code']);
//                if (!empty($settlement)) {
//                    $settlement_name = implode(',', array_column($settlement, 'settlement_name'));
//                    $list[$key]['supplier_settlement'] = $settlement_name;
//                } else {
//                    $list[$key]['supplier_settlement'] = '';
//                }
                //三月合作金额判断
                $list[$key]['cooperation_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['cooperation_amount'] : 0;
                $list[$key]['payment_days_online_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['payment_days_online_amount'] : 0;
                $list[$key]['payment_days_offline_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['payment_days_offline_amount'] : 0;
                $list[$key]['no_payment_days_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['no_payment_days_amount'] : 0;




                $list[$key]['cooperation_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['cooperation_amount'] : 0;
                $list[$key]['payment_days_online_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['payment_days_online_amount'] : 0;
                $list[$key]['payment_days_offline_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['payment_days_offline_amount'] : 0;
                $list[$key]['no_payment_days_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['no_payment_days_amount'] : 0;




//                if(isset($cooperation_amount[$value['supplier_code']])){
//                if (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0 && $list[$key]['cooperation_amount'] > $params['max_nineth_data']) {
//                    unset($list[$key]);
//                }
//                if (isset($params['min_nineth_data']) && strlen($params['min_nineth_data']) > 0 && $list[$key]['cooperation_amount'] < $params['min_nineth_data']) {
//                    if (isset($list[$key]))
//                        unset($list[$key]);
//                }
//                }
                //关联供应商显示
                $relation_info_show = [];
                $relation_info = $this->get_relation_supplier_detail($value['supplier_code'],false);
                if (!empty($relation_info)) {
                    foreach ($relation_info as $re_info) {
                        $relation_info_show[] = $re_info;

                    }

                }


                $list[$key]['relation_info'] = $relation_info_show;

                //查看禁用了多少次
                $forbid_times = $this->purchase_db->select('count(*) as num')->from('operator_log')->where(['record_number'=>$value['supplier_code'],'record_type'=>'supplier_update_log','operate_type'=>SUPPLIER_NORMAL_DISABLE])->get()->row_array();
                $list[$key]['forbid_times'] = !empty($forbid_times['num'])?$forbid_times['num']:0;
                $list[$key]['is_complete'] = !empty($value['is_complete'])?'是':'否';

            }
        }

        return array(
            'list' => $list,
            'count'=>$count_num
        );

    }

    public function modify_supplier_relation($supplier_code,$relation_info)
    {


        //关联供应商模块开始
        $relation_supplier_codes = [];//关联供应商编码
        $relation_info = !empty($relation_info)?$relation_info:null;//关联供应商
        //历史关联供应商
        $old_relation_suppliers = $this->supplier_model->get_relation_supplier_detail($supplier_code,false);

        if (!empty($relation_info)) {
            $relation_supplier_codes = array_column($relation_info,'relation_code');
            $num = count(array_unique($relation_supplier_codes));
            if ($num!=count($relation_info)) {
                throw new Exception("关联供应商有重复的");

            }
            foreach ($relation_info as $code) {
                if ($code['relation_code'] == $supplier_code) {
                    throw new Exception("不能选定自己作为关联供应商");

                }

            }


        }

        if (!empty($old_relation_suppliers)||!empty($relation_supplier_codes)) {
            $old_supplier_arr=[];
            if (!empty($old_relation_suppliers)) {
                $old_supplier_arr = array_keys($old_relation_suppliers);

            }

            $add_info = array_diff($relation_supplier_codes,$old_supplier_arr);//添加的关联供应商
            $del_info = array_diff($old_supplier_arr,$relation_supplier_codes);//删除的关联供应商
            $update_info = array_intersect($relation_supplier_codes,$old_supplier_arr);//更新的供应商信息

            if (!empty($add_info)) {
                $add_str = implode(',',$add_info);
                $change_data_log['supplier_relation'][] = '添加了关联供应商:'.$add_str;

            }
            if (!empty($del_info)) {
                $del_str = implode(',',$del_info);
                $change_data_log['supplier_relation'][] = '删除了关联供应商:'.$del_str;

            }

            if (!empty($update_info)) {


                foreach ($old_relation_suppliers as $su_key=> $old_supplier_info) {
                    foreach ($update_info as $update) {
                        if ($update == $su_key) {
                            foreach ($relation_info as $r_info) {
                                if ($r_info['relation_code'] == $update) {
                                    $type = $r_info['supplier_type']??'';
                                    $reason = $r_info['supplier_reason']??'';
                                    $type_remark = $r_info['supplier_type_remark']??'';
                                    $reason_remark= $r_info['supplier_reason_remark']??'';

                                }

                            }

                            if ($type!=$old_supplier_info['supplier_type']) {
                                $change_data_log['supplier_relation'][] ='关联供应商'.$update. '更改了关联供应商关联类型由'.getSupplierRelationType($old_supplier_info['supplier_type']).'更改成'.getSupplierRelationType($type);


                            }
                            if ($reason!=$old_supplier_info['supplier_reason']) {
                                $change_data_log['supplier_relation'][] ='关联供应商'.$update. '更改了关联供应商关联原因由'.getSupplierRelationReason($old_supplier_info['supplier_reason']).'更改成'.getSupplierRelationReason($reason);


                            }
                            if ($type_remark!=$old_supplier_info['supplier_type_remark']) {
                                $change_data_log['supplier_relation'][] ='关联供应商'.$update. '更改了关联供应商关联备注由'.$old_supplier_info['supplier_type_remark'].'更改成'.$type_remark;


                            }
                            if ($reason_remark!=$old_supplier_info['supplier_reason_remark']) {
                                $change_data_log['supplier_relation'][] ='关联供应商'.$update. '更改了关联供应商原因备注由'.$old_supplier_info['supplier_reason_remark'].'更改成'.$reason_remark;


                            }
                        }


                    }

                }


            }

            if ($relation_info){//更新供应商
                foreach ($relation_info as $info) {
                    $insert =[];
                    $insert['supplier_code'] = $supplier_code;
                    $insert['relation_code'] = $info['relation_code'];
                    $insert['supplier_type'] = $info['supplier_type'];
                    $insert['supplier_reason'] = $info['supplier_reason'];
                    $insert['supplier_type_remark'] = $info['supplier_type_remark']??'';
                    $insert['supplier_reason_remark'] = $info['supplier_reason_remark']??'';
                    $insert_data['relation_supplier'][] = $insert;

                }


            }

            if (empty($relation_supplier_codes)&&!empty($old_supplier_arr)) {//删除供应商
                $delete_data['relation_supplier'][] =  $old_relation_suppliers;

            }



        }
    }


        /**
     * 供应商审核列表
     * @param $params
     * @param $offset
     * @param $limit
     * @param int $page
     * @return array
     */
    public function audit_supplier_list($params,$offset,$limit,$page=1)
    {
        $audit_status_list = show_status();
        $source_arr = [1=>'采购系统',2=>'产品系统'];
        $apply_type_arr = [1=>'新增',2=>'更新',3=>'启用',4=>'关联供应商',5=>'临时转常规'];

        $is_info_change = [1=>'是',2=>'否'];
        $payment_optimization_arr =[1=>'是',2=>'否'];

        $down_settlement = $this->settlementModel->get_settlement($params);
        $down_settlement = array_column($down_settlement['list'],NULL,'settlement_code');

        $params = $this->table_query_filter($params);

        $query_builder = $this->purchase_db;

        //查询字段
        $fields = 'A.*,supplier.supplier_name,supplier.id as supplier_id,supplier.status';

        $query_builder->select($fields)
            ->from($this->audit_table . ' AS A')
            ->join($this->table_name.' AS supplier','supplier.supplier_code=A.supplier_code');

        if (isset($params['audit_status']) && !empty($params['audit_status'])) {
            $query_builder->where('A.audit_status', $params['audit_status']);
        }

        if (isset($params['apply_no']) && !empty($params['apply_no'])) {
            $query_builder->where('A.apply_no', $params['apply_no']);
        }

        if (isset($params['source']) && !empty($params['source'])) {
            $query_builder->where('A.source', $params['source']);
        }

        if (isset($params['apply_type']) && !empty($params['apply_type'])) {
            $query_builder->where('A.apply_type', $params['apply_type']);
        }

        if (isset($params['apply_id']) && !empty($params['apply_id'])) {
            $query_builder->where_in('A.create_user_id', $params['apply_id']);
        }

        if (isset($params['audit_id']) && !empty($params['audit_id'])) {
            $query_builder->where_in('A.audit_user_id', $params['audit_id']);
        }

        if (isset($params['apply_time_start']) && !empty($params['apply_time_start'])) {
            $query_builder->where('A.create_time>=', $params['apply_time_start']);
        }

        if (isset($params['apply_time_end']) && !empty($params['apply_time_end'])) {
            $query_builder->where('A.create_time<=', $params['apply_time_end']);
        }

        if (isset($params['audit_time_start']) && !empty($params['audit_time_start'])) {
            $query_builder->where('A.audit_time>=', $params['audit_time_start']);
        }

        if (isset($params['audit_time_end']) && !empty($params['audit_time_end'])) {
            $query_builder->where('A.audit_time<=', $params['audit_time_end']);
        }

        if (!empty($params['supplier_code'])) {
            $query_builder->where_in('A.supplier_code', $params['supplier_code']);
        }

        if (isset($params['settlement'])) {

            if(is_array($params['settlement'])) {
                $settle_ment_str = implode(',',$params['settlement']);
                $query_builder->where( "EXISTS (select supplier_code from pur_supplier_payment_info where supplier_code=A.supplier_code and supplier_settlement in ({$settle_ment_str}) and is_del=0)");
            }else {
                $query_builder->where('D.supplier_settlement', $params['settlement']);
            }
        }

        if (!empty($params['is_basic_change'])) {
            $query_builder->where('A.is_basic_change', $params['is_basic_change']);
        }

        if (!empty($params['is_relation_change'])) {
            $query_builder->where('A.is_relation_change', $params['is_relation_change']);
        }

        if (!empty($params['is_contact_change'])) {
            $query_builder->where('A.is_contact_change', $params['is_contact_change']);
        }

        if (!empty($params['is_payment_change'])) {
            $query_builder->where('A.is_payment_change', $params['is_payment_change']);
        }

        if (!empty($params['is_proof_change'])) {
            $query_builder->where('A.is_proof_change', $params['is_proof_change']);
        }

        if (!empty($params['payment_optimization'])) {
            $query_builder->where('A.payment_optimization', $params['payment_optimization']);
        }

        if (empty($params['audit_time_sort'])&&empty($params['create_time_sort'])) {
            $oder_by_condition = 'A.create_time desc,A.audit_time desc';
        }elseif (empty($params['audit_time_sort'])&&!empty($params['create_time_sort'])) {
            $oder_by_condition = 'A.create_time '.$params['create_time_sort'];
        }elseif (!empty($params['audit_time_sort'])&&empty($params['create_time_sort'])) {
            $oder_by_condition = 'A.audit_time '.$params['audit_time_sort'];
        }


        $clone_db   = clone($query_builder);
        $total      = $clone_db->count_all_results();//符合当前查询条件的总记录数

        // 3.分页参数
        $list = $query_builder->order_by($oder_by_condition)->limit($limit,$offset)->get()->result_array();

        if ($list) {
            $supplier_codes = array_column($list, 'supplier_code');


            // 获取支付方式, 结算方式
            $payment_info = $this->purchase_db->select("supplier_code,group_concat(payment_method) as payment_method,group_concat(supplier_settlement) as supplier_settlement")
                ->from('pur_supplier_payment_info')
                ->where_in('supplier_code', $supplier_codes)
                ->where('is_del', 0)
                ->group_by('supplier_code')
                ->get()
                ->result_array();
            $payment_info = array_column($payment_info, NULL, 'supplier_code');
            foreach ($payment_info as $key => &$item){
                if (!empty($item['payment_method'])){
                    $item['payment_method'] = explode(',',$item['payment_method']);
                    $item['payment_method'] = array_unique($item['payment_method']);
                    $item['payment_method'] = implode(',',$item['payment_method']);
                }
                if (!empty($item['supplier_settlement'])){
                    $item['supplier_settlement'] = explode(',',$item['supplier_settlement']);
                    $item['supplier_settlement'] = array_unique($item['supplier_settlement']);
                    $item['supplier_settlement'] = implode(',',$item['supplier_settlement']);
                }
            }

            foreach ($list as $key => $value) {
                $update_message = json_decode($value['message'],true);
                $list[$key]['audit_status_name'] = $audit_status_list[$value['audit_status']]??'';
                $list[$key]['is_basic_change'] = $is_info_change[$value['is_basic_change']]??'';
                $list[$key]['is_relation_change'] = $is_info_change[$value['is_relation_change']]??'';
                $list[$key]['is_contact_change'] = $is_info_change[$value['is_contact_change']]??'';
                $list[$key]['is_payment_change'] = $is_info_change[$value['is_payment_change']]??'';
                $list[$key]['is_proof_change'] = $is_info_change[$value['is_proof_change']]??'';
                $list[$key]['source'] = $source_arr[$value['source']]??'';
                $list[$key]['apply_type'] = $apply_type_arr[$value['apply_type']]??'';
                $list[$key]['payment_optimization'] = $payment_optimization_arr[$value['payment_optimization']]??'';



                $supplier_settlement= isset($payment_info[$value['supplier_code']]['supplier_settlement']) ? $payment_info[$value['supplier_code']]['supplier_settlement'] : '';


                //供应商结算方式
                if (isset($down_settlement)) {
                    if($supplier_settlement and strpos($supplier_settlement,',') !== false){
                        $supplier_settlements = explode(',',$supplier_settlement);
                        $_supplier_settlement = [];
                        foreach($supplier_settlements as $payment_method){
                            $_supplier_settlement[] = $down_settlement[$payment_method]['settlement_name']??'';
                        }
                        $list[$key]['settlement'] = implode(',',$_supplier_settlement);
                    }else{
                        $_supplier_settlement = $down_settlement[$supplier_settlement]['settlement_name']??'';
                        if ($_supplier_settlement) {
                            $list[$key]['settlement'] = $_supplier_settlement;
                        }
                    }

//                    pr($down_settlement);exit;
//                    $item[$key]['supplier_settlement'] = isset($downSettlement[$info['supplier_settlement']]) ? $downSettlement[$info['supplier_settlement']] : $info['supplier_settlement'];
                }











            }
        }
        //$buyers = $this->buyerModel->get_buyers();//采购员列表

        $apply_id = $this->purchase_db->select('create_user_id AS buyer_id,create_user_name AS buyer_name')
            ->from($this->audit_table)
            ->group_by('create_user_id')
            ->get()
            ->result_array();



        $return_data = [
            'data_list'   => [
                'value' => $list,
                'key' =>  [
                    "序号",
                    "审核状态",
                    "申请编号",
                    "供应商代码",
                    "供应商名称",
                    "结算方式",
                    "是否修改基本信息",
                    "是否修改关联供应商",
                    "是否修改联系方式",
                    "是否修改财务结算",
                    "是否修改资料证明",
                    "申请人",
                    "申请时间",
                    "审核人",
                    "审核时间",
                ],
                'drop_down_box' =>[
                    'audit_status'=>show_status(),
                    'apply_id'=> $apply_id,
                    'source'=>$source_arr,
                    'apply_type'=>$apply_type_arr,
                    'settlement'=>$down_settlement,
                    'is_info_change'=>$is_info_change,
                    'payment_optimization'=>[1=>'是',2=>'否']
                ],

            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit

            ],

        ];

        return $return_data;




    }



    /**
     * 生成 指定前缀的 最新编号（自动更新编号记录）
     * @author Dean
     * @param string    $order_prefix   前缀
     * @param int       $add_number     增量（默认 1）
     * @param int       $fixed_length   编号长度（默认 4，用来填充）
     * @return bool|string
     */
    public function get_prefix_new_number($order_prefix,$add_number = 1,$fixed_length = 4){
        if ($order_prefix == 'dj') {
            $table = 'supplier_level_grade_log';
        } else {
            $table = 'supplier_update_log';
        }
        $now_day = date('Ymd');
        $operator_key    = strtoupper('get_prefix_supplier_apply_'.$order_prefix.$now_day);
        $existsKeyNumber = $this->rediss->getData($operator_key);// 命令用于获取指定 key 的值。如果 key 不存在，返回 nil 。如果key 储存的值不是字符串类型，返回一个错误


        if(empty($existsKeyNumber)){
            $num_info = $this->purchase_db->select('count(*) as num')->from($table)->where('create_time>=',date('Y-m-d'))->get()->row_array();
            $num = $num_info['num']+$add_number;
            $number = $this->joinNumberStr($order_prefix,$num,$fixed_length,$now_day);// 编号不足长度 左边自动补0
            $this->rediss->setData($operator_key,$num);// 只是存储数字

        }else{
            $this->rediss->incrByData($operator_key,$add_number);// 命令将 key 中储存的数字加上指定的增量值
            $number_int_value = $this->rediss->getData($operator_key);
            $number = $this->joinNumberStr($order_prefix,$number_int_value,$fixed_length,$now_day);// 编号不足长度 左边自动补0

        }

        return $number;
    }


    /**
     * 拼接指定格式的 前缀
     * @param $prefix
     * @param $number
     * @param $fixed_length
     * @param $date_str
     * @return string
     */
    public function joinNumberStr($prefix, $number, $fixed_length,$date_str){
        return strtoupper($prefix).$date_str.str_pad($number, $fixed_length, "0", STR_PAD_LEFT);// 编号不足长度 左边自动补0
    }

    /**
     * 先获取采购系统在售中、试卖在售中、新系统开发中的sku
     * @param $supplier_code
     * @return array
     */
    public function get_confirm_sku_info($supplier_code)
    {
        $purchase_sku = $this->purchase_db->select('sku')
            ->from('product')
            ->where_in('product_status',[4,18,35])
            ->where('supplier_code',$supplier_code)
            ->where('product_type',1)
            ->where('is_multi!=2')
            ->get()
            ->result_array();

        $purchase_sku = !empty($purchase_sku)?array_column($purchase_sku,'sku'):[];
        $product_sku = [];

        $request_url = getConfigItemByName('api_config', 'product_system', 'getProdSkuInfo');
        $access_token = getOASystemAccessToken();
        $request_url = $request_url . '?access_token=' . $access_token;
        $params = [
            'supplierCode' => $supplier_code,
            'pageNumber' => 1,
            "pageSize"=>100
        ];

        $header = array('Content-Type: application/json');
        $results = getCurlData($request_url, json_encode($params), 'post', $header);
        $results = json_decode($results, true);

        if (!empty($results)&&$results['code'] == 200&&!empty($results['data'])) {
            $product_sku = array_column($results['data'],'sku');

        }

        return array_unique(array_merge($purchase_sku,$product_sku));
    }

    //获取等级分数审核记录
    public function get_level_grade_log($supplier_code,$status_list)
    {
        return $this->purchase_db->select('*')->from($this->audit_level_grade_table)->where_in('audit_status',$status_list)->where('supplier_code',$supplier_code)->get()->result_array();


    }

    //获取等级审核详情
    public function level_grade_detail($id)
    {
        return $this->purchase_db->select('*')->from($this->audit_level_grade_table)->where('id',$id)->get()->row_array();


    }
    /*
     * @desc 供应商等级分数审核
     * @ $id  int 审核明细 $new_status int 审核后状态 $remarks string 备注
     * @ return array
     */


    public function do_level_grade_supplier($id,$new_status,$remarks)
    {
        $result = ['code'=>false,'msg'=>''];
        $log_detail = $this->level_grade_detail($id);

        $audit_user = getActiveUserName();
        $audit_id   = getActiveUserId();
        $supplier_code = $log_detail['supplier_code'];
        try{

            //驳回
            if (in_array($new_status,[SUPPLIER_MANAGE_REJECT_LEVEL_GRADE,SUPPLIER_SUPPLIER_REJECT_LEVEL_GRADE
                ,SUPPLIER_PURCHASE_REJECT_LEVEL_GRADE])) {
                $update_data = ['audit_status'=>$new_status,'reject_remark'=>$remarks,'audit_time'=>date('Y-m-d H:i:s'),'audit_user_id'=>$audit_id,'audit_user_name'=>$audit_user];
                switch($new_status){
                    case SUPPLIER_MANAGE_REJECT_LEVEL_GRADE:
                        $hint = '供应链负责人';
                        break;
                    case SUPPLIER_SUPPLIER_REJECT_LEVEL_GRADE:
                        $hint = '供应链审核';
                        break;
                    case SUPPLIER_PURCHASE_REJECT_LEVEL_GRADE:
                        $hint = '采购审核';
                        break;
                    default:
                        $hint = '';

                }
                operatorLogInsert(
                    [
                        'id'      => $supplier_code,
                        'type'    => 'supplier_update_log',
                        'content' => "{$hint}-审核不通过",
                        'detail'  => '驳回原因：'.$remarks,
                        'operate_type' => SUPPLIER_LEVEL_GRADE_OPR
                    ]
                );

                $flag = $this->purchase_db->update($this->audit_level_grade_table,$update_data)->where('id',$id);
                if (!$flag) {
                    throw new Exception('供应商等级分数审核失败');
                }



            } else {
                //审核通过

                switch($new_status){
                    case SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE:
                        $hint = '采购审核';
                        break;
                    case SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE:
                        $hint = '供应链审核';
                        break;

                    case SUPPLIER_REVIEW_PASSED_LEVEL_GRADE:
                        $hint = '供应链负责人';
                        break;

                    default :
                        $hint = '';
                }
                if ($new_status == SUPPLIER_REVIEW_PASSED_LEVEL_GRADE) {
                    $update_data = ['audit_status'=>$new_status,'audit_time'=>date('Y-m-d H:i:s'),'audit_user_id'=>$audit_id,'audit_user_name'=>$audit_user];
                    $modify_data = ['supplier_level'=>$log_detail['new_supplier_level'],'supplier_grade'=>$log_detail['new_supplier_grade']];

                    $res1 = $this->purchase_db->where('id',$id)->update($this->audit_level_grade_table,$update_data);
                    $res2 = $this->purchase_db->where('supplier_code',$supplier_code)->update($this->table_name,$modify_data);

                    if ($res1&&$res2) {
                        $update_data['msg'] = '审核通过';

                    } else {
                        $update_data['msg'] = '审核通过，更新供应商数据失败';

                    }



                } else {
                    $update_data = ['audit_status'=>$new_status,'audit_time'=>date('Y-m-d H:i:s'),'audit_user_id'=>$audit_id,'audit_user_name'=>$audit_user];
                    $flag = $this->purchase_db->where('id',$id)->update($this->audit_level_grade_table,$update_data);


                    if (!$flag) {
                        throw new Exception('审核失败');
                    }


                }

                operatorLogInsert(
                    [
                        'id'      => $supplier_code,
                        'type'    => 'supplier_update_log',
                        'content' => "{$hint}-审核通过",
                        'detail'  => '审核通过',
                        'operate_type' => SUPPLIER_LEVEL_GRADE_OPR
                    ]
                );



            }
            $result['code'] = true;

        }catch(Exception $e){
            $result['msg'] = $e->getMessage();


        }

        return $result;




    }


    public function audit_supplier_level_grade_list($params,$offset,$limit,$page=1)
    {
        $audit_status_list = show_status_level_grade();


        $params = $this->table_query_filter($params);


        $query_builder = $this->purchase_db;

        //查询字段
        $fields = 'A.*,supplier.supplier_name';

        $query_builder->select($fields)
            ->from($this->audit_level_grade_table . ' AS A')
            ->join($this->table_name.' AS supplier','supplier.supplier_code=A.supplier_code')
        ;





        if (isset($params['audit_status']) && !empty($params['audit_status'])) {

            $query_builder->where('A.audit_status', $params['audit_status']);

        }

        if (isset($params['apply_no']) && !empty($params['apply_no'])) {

            $query_builder->where('A.apply_no', $params['apply_no']);

        }




        if (isset($params['apply_id']) && !empty($params['apply_id'])) {

            $query_builder->where('A.create_user_id', $params['apply_id']);

        }

        if (isset($params['audit_id']) && !empty($params['audit_id'])) {

            $query_builder->where('A.audit_user_id', $params['audit_id']);

        }

        if (isset($params['apply_time_start']) && !empty($params['apply_time_start'])) {

            $query_builder->where('A.create_time>=', $params['apply_time_start']);

        }

        if (isset($params['apply_time_end']) && !empty($params['apply_time_end'])) {

            $query_builder->where('A.create_time<=', $params['apply_time_end']);

        }

        if (isset($params['audit_time_start']) && !empty($params['audit_time_start'])) {

            $query_builder->where('A.audit_time>=', $params['audit_time_start']);

        }

        if (isset($params['audit_time_end']) && !empty($params['audit_time_end'])) {

            $query_builder->where('A.audit_time<=', $params['audit_time_end']);

        }

        if (!empty($params['supplier_code'])) {
            $query_builder->where_in('A.supplier_code', $params['supplier_code']);


        }







        $clone_db = clone($query_builder);
        $total=$clone_db->count_all_results();//符合当前查询条件的总记录数



        // 3.分页参数

        $list = $query_builder->order_by('A.id DESC')->limit($limit,$offset)->get()->result_array();







        if ($list) {


            foreach ($list as $key => $value) {


                $list[$key]['audit_status_name'] = $audit_status_list[$value['audit_status']]??'';
                $list[$key]['old_supplier_level_name'] = !empty($value['old_supplier_level'])?getSupplierLevel($value['old_supplier_level']):'';
                $list[$key]['new_supplier_level_name'] = !empty($value['new_supplier_level'])?getSupplierLevel($value['new_supplier_level']):'';







            }
        }
        $buyers = $this->buyerModel->get_buyers();//采购员列表


        $return_data = [
            'data_list'   => [
                'value' => $list,
                'key' =>  [
                    "序号",
                    "审核状态",
                    "申请编号",
                    "供应商代码",
                    "供应商名称",
                    "变更前分值",
                    "变更后分值",
                    "变更前等级",
                    "变更后等级",
                    "申请人",
                    "申请时间",
                    "审核人",
                    "审核时间",
                ],
                'drop_down_box' =>[
                    'audit_status'=>show_status_level_grade(),
                    'apply_id'=>$buyers['list'],
                    'supplier_level'=>getSupplierLevel()
                ],

            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit

            ],

        ];

        return $return_data;




    }

    public function update_supplier_business_line($all)
    {
        //求前一天时间
        $before = date('Y-m-d',strtotime("-1day"));
        if ($all) {
            $cal_data = $this->purchase_db->select('purchase.supplier_code,GROUP_CONCAT(purchase.purchase_type_id) AS purchase_type_id ')->from('warehouse_results  as results')
                ->join('pur_purchase_order as purchase','purchase.purchase_number=results.purchase_number')
                ->group_by('purchase.supplier_code')->get()->result_array();

        } else {
            $cal_data = $this->purchase_db->select('purchase.supplier_code,GROUP_CONCAT(purchase.purchase_type_id) AS purchase_type_id ')->from('warehouse_results  as results')
                ->join('pur_purchase_order as purchase','purchase.purchase_number=results.purchase_number')->where('results.instock_date>=',$before)->where('results.instock_date<=',$before.' 23:59:59')
                ->group_by('purchase.supplier_code')->get()->result_array();


        }

        



        if (!empty($cal_data)) {

            foreach ($cal_data as $cal) {
                //获取供应商信息

                if ($cal['supplier_code']) {
                    $supplier_info = $this->get_supplier_info($cal['supplier_code'],false);
                    if (!empty($supplier_info)&&(!in_array($supplier_info['status'],[2,4,7]))&&$supplier_info['business_line']!=3) {
                        $purchase_type_list = array_unique(explode(',',$cal['purchase_type_id']));
                        if (!empty($purchase_type_list)) {
                            $in_land_arr = array_intersect($purchase_type_list,[1,3,4,5]);
                            $oversea_arr = array_intersect($purchase_type_list,[2,6]);

                            if (!empty($in_land_arr)&&!empty($oversea_arr)) {
                                $purchase_type_id = 3;

                            } elseif(!empty($in_land_arr)&&empty($oversea_arr)){
                                $purchase_type_id = 1;


                            } elseif(!empty($oversea_arr)&&empty($in_land_arr)){
                                $purchase_type_id = 2;


                            } else {
                                $purchase_type_id = 0;
                            }
                            if (!empty($purchase_type_id)&&$purchase_type_id!=$supplier_info['business_line']) {
                                $this->purchase_db->where('supplier_code',$cal['supplier_code'])->update('supplier',['business_line'=>$purchase_type_id]);

                            }





                        }



                    }

                }

            }

        }

    }


//($params,$offset,$limit,$page=1,$export=false)
    /**
     * @desc 获取供应商列表(分页)
     * @return array()
     **@author Jackson
     * @Date 2019-01-21 17:01:00
     */
    public function supplier_visit_list($params = [],$export=false)
    {
        $supplier_level_list = getSupplierLevel();
        $develop_source_arr = [1=>'门户系统',2=>'产品系统'];

        $platform_source_arr = [1=>'线下',2=>'线上阿里店铺',3=>'线上淘宝',4=>'线上拼多多',5=>'线上京东',6=>'其他线上平台'];

        $business_line_arr =[1=>'国内',2=>'海外',3=>'国内/海外'];

        $cooperation_status = getCooperationStatus();



        $params = $this->table_query_filter($params);



        $query_builder = $this->purchase_db;

        //查询字段
        $fields = 'A.is_push_purchase,A.is_gateway,A.id,A.supplier_code,A.supplier_name,A.supplier_settlement,A.payment_method,A.supplier_level,A.supplier_source,A.create_user_name,A.sku_num,A.store_link,A.status,A.audit_status,A.search_status,
                A.is_complete,A.is_cross_border,A.is_diversion_status,A.business_line,A.develop_source,A.platform_source,A.restart_date,A.sku_sale_num,A.sku_no_sale_num,A.sku_other_num,A.disabled_times,A.supplier_grade,A.create_time,A.order_num,A.sku_shipping_num,C.linelist_cn_name,V.apply_no,V.apply_name,V.apply_sector,V.visit_status,V.start_time,V.end_time,V.create_time as apply_time,V.visit_level,V.visit_grade,V.id as visit_id,V.visit_aim';

        $query_builder->select($fields)
            ->from($this->supplier_visit_table . ' AS V')
            ->join($this->table_name . ' AS A', "A.supplier_code=V.supplier_code ", 'LEFT')
            ->join($this->supplier_product_line_table_name . ' AS B', "A.supplier_code=B.supplier_code AND B.status=1", 'LEFT')
            ->join($this->product_line_table_name . " AS C", "B.first_product_line=C.product_line_id", 'LEFT')
            ->join("pur_supplier_payment_info AS D", "A.supplier_code=D.supplier_code AND D.is_del=0", 'LEFT')
            ->join($this->pur_supplier_cooperation_amount . " AS E", "A.supplier_code=E.supplier_code", 'LEFT');

        if ($export&&!empty($params['visit_ids'])) {
            $ids_arr =explode(',',$params['visit_ids']);
            $this->purchase_db->where_in('V.id',$ids_arr);

        } else {


            if (isset($params['payment_method'])) {
                $query_builder->where('D.payment_method', $params['payment_method']);
            }
            $searchSupplierCode = [];
            if( isset($params['overseas_supplier_code']) ){

                $searchSupplierCode = array_merge($searchSupplierCode,$params['overseas_supplier_code']);

            }

            if( isset($params['domestic_supplier_code']) ){

                $searchSupplierCode = array_merge($searchSupplierCode,$params['domestic_supplier_code']);
            }
            if(!empty($searchSupplierCode)){

                if(count($searchSupplierCode)>2000){

                    $domestic_supplier_code = array_chunk($searchSupplierCode,2000);
                    $query_builder->where("( 1=1");
                    foreach($domestic_supplier_code as $domestic_supplier_code_value){

                        $query_builder->or_where_in('A.supplier_code',$domestic_supplier_code_value);
                    }
                    $query_builder->where(" 1=1)");
                }else{
                    $query_builder->where_in('A.supplier_code', $searchSupplierCode);
                }
            }




            if( isset($params['reportloss_start']) && isset($params['reportloss_end'])){

                $query_builder->where(" A.supplier_code IN (
                        SELECT
                            supplier_code
                        FROM
                            pur_purchase_order AS orders
                        LEFT JOIN `pur_purchase_order_reportloss` AS `reportloss` ON `reportloss`.`pur_number` = `orders`.`purchase_number`
                        WHERE reportloss.status=4
                        GROUP BY
                            orders.supplier_code
                        HAVING
                            SUM(loss_totalprice) >= {$params['reportloss_start']}  AND SUM(loss_totalprice)<={$params['reportloss_end']}
                    )"
                );
            }

            if (isset($params['buyer'])) {
                if ($params['buyer']==0) {
                    $query_builder->where("A.supplier_code NOT IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_type=1 GROUP BY supplier_code)");

                } else {
                    $buyer_str = implode(',',$params['buyer']);
                    $query_builder->where("A.supplier_code IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_id in ({$buyer_str}) AND pur_{$this->buyer_name}.status='1' and buyer_type in (1,2,3)   GROUP BY supplier_code)");

                }

            }
            if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
                if (is_array($params['supplier_code'])) {
                    $query_builder->where_in('A.supplier_code', $params['supplier_code']);
                } else {
                    $query_builder->where('A.supplier_code', $params['supplier_code']);
                }
            }

            if(isset($params['is_gateway']) && $params['is_gateway'] != NULL){

                if( $params['is_gateway'] == 1){
                    $query_builder->where_in('A.is_gateway',SUGGEST_IS_GATEWAY_YES)->where('A.is_push_purchase',1);
                }else{
                    $query_builder->where('(A.is_gateway',SUGGEST_IS_GATEWAY_NO)->or_where('A.is_push_purchase=2)');
                }
            }
            if (isset($params['create_user_id'])&&!empty($params['create_user_id'])) {
                $query_builder->where_in('V.create_user_id', $params['create_user_id']);
            }
            if (isset($params['supplier_settlement'])) {

                if(is_array($params['supplier_settlement'])) {
                    $query_builder->where_in('D.supplier_settlement', $params['supplier_settlement']);
                }else {
                    $query_builder->where('D.supplier_settlement', $params['supplier_settlement']);
                }
            }
            if (isset($params['supplier_level'])&&!empty($params['supplier_level'])) {
                $query_builder->where_in('A.supplier_level', $params['supplier_level']);
            }
            if (isset($params['is_cross_border'])) {
                $query_builder->where('A.is_cross_border', $params['is_cross_border']);
            }
            if (isset($params['status'])) {
                //$query_builder->where('A.status',$params['status']);
                if(is_array($params['status'])){
                    $query_builder->where_in('A.audit_status', $params['status']);
                }else{
                    $query_builder->where('A.audit_status', $params['status']);
                }
            }
            if (isset($params['first_product_line'])) {
                $query_builder->where_in('B.first_product_line', $params['first_product_line']);
            }

            if (isset($params['cooperation_status'])) {
                $query_builder->where('A.status', $params['cooperation_status']);
            }

            if (isset($params['supplier_source'])) {
                $query_builder->where('A.supplier_source', $params['supplier_source']);
            }


            if (isset($params['is_diversion_status'])) {
                $query_builder->where('A.is_diversion_status', $params['is_diversion_status']);
            }

            if (isset($params['is_complete']) && is_numeric($params['is_complete'])) {
                $query_builder->where('A.is_complete', $params['is_complete']);
            }
            //有效sku数量
            if (isset($params['min_sku_num'])) {
                $query_builder->where('A.sku_num >=', intval($params['min_sku_num']));
            }
            if (isset($params['max_sku_num'])) {
                $query_builder->where('A.sku_num <=', intval($params['max_sku_num']));
            }

            if (!empty($params['cooperation_letter'])) {
                $query_builder->join("pur_supplier_images AS images", "A.supplier_code=images.supplier_code AND images.image_type='cooperation_letter'", 'LEFT');

                if ($params['cooperation_letter'] == 1) {
                    $query_builder->where('images.image_url IS NOT NULL');

                } else {
                    $query_builder->where('images.image_url IS  NULL');

                }


            }
            //关联供应商
            if (!empty($params['is_relation'])) {

                $query_builder->join("pur_relation_supplier AS relation", "A.supplier_code=relation.supplier_code", 'LEFT');



                if ($params['is_relation'] == 1) {
                    $query_builder->where('relation.supplier_code is not null');

                } else {
                    $query_builder->where("relation.supplier_code  is null");



                }


            }

            //备用下单次数
            if (!empty($params['min_order_num'])) {
                $query_builder->where('A.order_num >=', intval($params['min_order_num']));


            }


            if (!empty($params['max_order_num'])) {
                $query_builder->where('A.order_num <=', intval($params['max_order_num']));


            }



            if (isset($params['check'])&&!empty($params['check'])) {






                $query_builder->where("A.supplier_code IN(SELECT supplier_code FROM pur_{$this->buyer_name} WHERE buyer_id = {$params['check']} AND pur_{$this->buyer_name}.status='1' and buyer_type = '10'   GROUP BY supplier_code)");




            }


            if ((isset($params['min_nineth_data']) && strlen($params['min_nineth_data']) > 0) || (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0)) {
                $params['min_nineth_data'] = $params['min_nineth_data'] ?? 0;
                $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
                $end = date('Y-m-d H:i:s');
                $nineth_sql = "A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING (sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) >= {$params['min_nineth_data']})";
//            $query_builder->where(" D.calculate_date >= '{$start}' AND D.calculate_date  <= '{$end}' GROUP BY D.supplier_code HAVING sum(D.cooperation_amount) >= {$params['min_nineth_data']}");
            }
            if (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0) {
//            $start = date('Y-m-d 00:00:00', strtotime("-90 days"));
//            $end = date('Y-m-d H:i:s');
//            $query_builder->where("A.supplier_code IN (SELECT supplier_code FROM {$this->pur_supplier_cooperation_amount} WHERE {$this->pur_supplier_cooperation_amount}.calculate_date >='{$start}' AND {$this->pur_supplier_cooperation_amount}.calculate_date <= '{$end}' GROUP BY supplier_code HAVING sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}");
                $nineth_sql .= " AND sum({$this->pur_supplier_cooperation_amount}.cooperation_amount) <= {$params['max_nineth_data']}";
            }
            if (isset($nineth_sql)) {
                $query_builder->where($nineth_sql . ')');
            }

            if (!empty($params['develop_source'])) {
                $query_builder->where('A.develop_source',$params['develop_source']);

            }
            if (!empty($params['platform_source'])) {
                $query_builder->where('A.platform_source',$params['platform_source']);

            }

            if (!empty($params['business_line'])) {
                $query_builder->where('A.business_line',$params['business_line']);

            }


            if (!empty($params['create_time_start'])) {
                $query_builder->where('A.create_time >=', $params['create_time_start']);


            }

            if (!empty($params['create_time_end'])) {
                $query_builder->where('A.create_time <=', $params['create_time_end']);


            }


            if (!empty($params['restart_date_start'])) {
                $query_builder->where('A.restart_date >=', $params['restart_date_start']);


            }

            if (!empty($params['restart_date_end'])) {
                $query_builder->where('A.restart_date <=', $params['restart_date_end']);


            }


            if (!empty($params['supplier_visit_status'])) {
                $status_where = '';
                $now_time =date('Y-m-d H:i:s');
                foreach ($params['supplier_visit_status'] as $status_k=> $status_v) {

                    if ($status_k!=0) {
                        $status_where.=' OR ';

                    }


                   if ($status_v ==SUPPLIER_VISIT_WAIT_VISITING) {
                       $status_where .="(visit_status=2 and start_time>'{$now_time}')";

                   } elseif ($status_v == SUPPLIER_VISIT_IN_VISITING){
                       $status_where .="(visit_status=2 and start_time<='{$now_time}' and end_time>='{$now_time}')";
                   } elseif ($status_v == SUPPLIER_VISIT_WAIT_REPORT) {
                       $status_where .="(visit_status=2  and end_time<'{$now_time}')";


                   } else {
                       $status_where .="(visit_status={$status_v})";

                   }

                }
                $query_builder->group_start();
                $query_builder->where($status_where);
                $query_builder->group_end();







            }

            if (!empty($params['supplier_visit_depart'])) {
                $query_builder->where_in('V.apply_sector',$params['supplier_visit_depart'] );


            }

            if (!empty($params['apply_user_id'])) {
                $query_builder->where_in('V.apply_id',$params['apply_user_id'] );

            }


            if (isset($params['supplier_visit_aim']) && !empty($params['supplier_visit_aim'])) {
                $query_builder->group_start();

                foreach ($params['supplier_visit_aim'] as $aim_id) {

                    $query_builder ->or_where("FIND_IN_SET({$aim_id},V.visit_aim) !=",0);



                }

                $query_builder->group_end();

            }





        }



        $query_builder->order_by('V.id DESC')->group_by('V.id');

        // 3.分页参数
        $pageSize = query_limit_range(isset($params['limit']) ? $params['limit'] : 0);
        $page = !isset($params['offset']) || intval($params['offset']) <= 0 ? 1 : intval($params['offset']);
        $offset = ($page - 1) * $pageSize;
        // 三个月合作金额搜索条件

        $clone_db = clone($query_builder);
        $total=$clone_db->count_all_results();//符合当前查询条件的总记录数


        if ($export) {
            $list = $query_builder->get()->result_array();


        } else {
            $list = $query_builder->offset($offset)->limit($pageSize)->get()->result_array();

        }


        if ($list) {
            $supplier_codes = array_column($list, 'supplier_code');
            $visit_info     = $this->get_visit_times($supplier_codes);//拜访次数
            // 获取采购员信息
            $buyer_lists = $this->purchase_db->select("supplier_code,buyer_type,buyer_name as buyer")
                ->from($this->buyer_name)
                ->where('status', 1)
                ->where_in('supplier_code', $supplier_codes)
                ->order_by('buyer_type asc')
                ->get()
                ->result_array();
            $buyer_lists = arrayKeyToColumnMulti($buyer_lists, 'supplier_code');

            $supplier_codes = array_column($list, 'supplier_code');

            // 获取支付方式, 结算方式
            $payment_info = $this->purchase_db->select("supplier_code,group_concat(payment_method) as payment_method,group_concat(supplier_settlement) as supplier_settlement")
                ->from('pur_supplier_payment_info')
                ->where_in('supplier_code', $supplier_codes)
                ->where('is_del', 0)
                ->group_by('supplier_code')
                ->get()
                ->result_array();
            $payment_info = array_column($payment_info, NULL, 'supplier_code');
            foreach ($payment_info as $key => &$item){
                if (!empty($item['payment_method'])){
                    $payment_method_list = explode(',',$item['payment_method']);
                    $payment_method_list = array_unique($payment_method_list);
                    $item['payment_method'] = implode(',',$payment_method_list);
                }
                if (!empty($item['supplier_settlement'])){
                    $supplier_settlement_list = explode(',',$item['supplier_settlement']);
                    $supplier_settlement_list = array_unique($supplier_settlement_list);
                    $item['supplier_settlement'] = implode(',',$supplier_settlement_list);
                }
            }
            //三个月合作金额
            $cooperation_amount = $this->get_supplier_cooperation_amount($supplier_codes);

            //上个月合作金额

            $before_month_start = DATE('Y-m-01',strtotime("-1month"));
            $before_month_end   = DATE('Y-m-t',strtotime("-1month"));

            $cooperation_amount_one_month = $this->get_supplier_cooperation_amount($supplier_codes,$before_month_start,$before_month_end);

            $supplier_reportData = [];
            if( !empty($list) ){

                $supplier_codes = array_filter(array_column( $list,"supplier_code"));

                $supplier_reportData_query = $this->purchase_db->select(" SUM(loss_totalprice) AS loss_totalprice,orders.supplier_code")->from("purchase_order AS orders")->join(" pur_purchase_order_reportloss AS reportloss","reportloss.pur_number = orders.purchase_number");
                $supplier_reportData = $supplier_reportData_query->where("reportloss.status",4)->where_in("orders.supplier_code",$supplier_codes)->group_by("orders.supplier_code")->get()->result_array();
                if( !empty($supplier_reportData)){
                    $supplier_reportData = array_column( $supplier_reportData,NULL,"supplier_code");
                }


            }
            foreach ($list as $key => $value) {
                $list[$key]['business_line'] = $business_line_arr[$value['business_line']]??'';
                $list[$key]['develop_source'] = $develop_source_arr[$value['develop_source']]??'';
                $list[$key]['platform_source'] = $platform_source_arr[$value['platform_source']]??'';
                $list[$key]['cooperation_status'] = $cooperation_status[$value['status']]??'';

                $now_buyer = isset($buyer_lists[$value['supplier_code']]) ? $buyer_lists[$value['supplier_code']] : [];
                $list[$key]['buyer'] = array_column($now_buyer, 'buyer', 'buyer_type');

                if(  isset($supplier_reportData[$value['supplier_code']]) && $supplier_reportData[$value['supplier_code']]['loss_totalprice'] != NULL ){

                    $list[$key]['loss_money'] = $supplier_reportData[$value['supplier_code']]['loss_totalprice'];
                }else{

                    $list[$key]['loss_money'] = 0;
                }
//                $list[$key]['supplier_level']= !empty($supplier_level_list[$list[$key]['supplier_level']])?$supplier_level_list[$list[$key]['supplier_level']]:$list[$key]['supplier_level'];
                if(!empty($list[$key]['supplier_level'])) {
                    if (!empty($supplier_level_list[$list[$key]['supplier_level']])) {
                        $list[$key]['supplier_level'] = $supplier_level_list[$list[$key]['supplier_level']];
                    }else{
                        $list[$key]['supplier_level'];
                    }
                }else{
                    $list[$key]['supplier_level'] = '';
                }

                $list[$key]['payment_method'] = isset($payment_info[$value['supplier_code']]) ? $payment_info[$value['supplier_code']]['payment_method'] : '';
                $list[$key]['supplier_settlement'] = isset($payment_info[$value['supplier_code']]) ? $payment_info[$value['supplier_code']]['supplier_settlement'] : '';
                //获取最新一条审核备注
                $sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log AS a
	WHERE record_number = '{$value['supplier_code']}' AND record_type
	= 'SUPPLIER_UPDATE_LOG' AND operate_route = 'supplier/Supplier/supplier_review' order by id desc limit 1";

                $detail_list = $this->purchase_db->query($sql)->row_array();
//                $detail_list = array_column($detail_list, 'content_detail', 'supplier_code');
                $detail_list[$value['supplier_code']] = $detail_list['content_detail'];

                $list[$key]['contract_notice'] = isset($detail_list[$value['supplier_code']]) ? $detail_list[$value['supplier_code']] : '';

                if( $value['is_gateway'] == SUGGEST_IS_GATEWAY_YES && $value['is_push_purchase'] == 1){

                    $list[$key]['is_gateway_ch'] = '是';
                }else{
                    $list[$key]['is_gateway_ch'] = '否';
                }

                if ($value['status'] == IS_DISABLE) {//如果供应商处于禁用状态 显示禁用备注

                    $query_sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log WHERE record_number = '{$value['supplier_code']}' AND record_type
	in ('SUPPLIER_UPDATE_LOG','PUR_SUPPLIER') AND operate_route = 'supplier/Supplier/supplier_disable' AND content = '供应商禁用成功' order by id desc limit 1";
                    $fobidden_remark = $this->purchase_db->query($query_sql)->row_array();
//                    $fobidden_remark_list = array_column($fobidden_remark, 'content_detail', 'supplier_code');
                    $fobidden_remark_list[$value['supplier_code']] = $fobidden_remark['content_detail'];

                    $current_forbidden_remark = isset($fobidden_remark_list[$value['supplier_code']]) ? mb_substr($fobidden_remark_list[$value['supplier_code']], 4) : '';
                    $current_forbidden_remark ? $list[$key]['contract_notice'] .= PHP_EOL . '禁用原因:' . $current_forbidden_remark : '';
                }elseif($value['status'] == PRE_DISABLE){




                    $query_sql = "SELECT `content_detail`,`record_number` AS `supplier_code`,id FROM pur_operator_log WHERE record_number = '{$value['supplier_code']}' AND record_type
	in ('SUPPLIER_UPDATE_LOG','PUR_SUPPLIER') AND operate_route = 'supplier/supplier/pre_disable' AND content = '供应商预禁用成功' order by id desc limit 1";
                    $fobidden_remark = $this->purchase_db->query($query_sql)->row_array();
//                    $fobidden_remark_list = array_column($fobidden_remark, 'content_detail', 'supplier_code');
                    $fobidden_remark_list[$value['supplier_code']] = $fobidden_remark['content_detail'];

                    $current_forbidden_remark = isset($fobidden_remark_list[$value['supplier_code']]) ? mb_substr($fobidden_remark_list[$value['supplier_code']], 5) : '';
                    $current_forbidden_remark ? $list[$key]['contract_notice'] .= PHP_EOL . '预禁用原因:' . $current_forbidden_remark : '';

                } else {
                    //更新或启用新增申请备注

                    $new_query_sql = "SELECT `ext`,`record_number` AS `supplier_code`,id FROM pur_operator_log WHERE record_number = '{$value['supplier_code']}' AND record_type 
	= 'SUPPLIER_UPDATE_LOG' order by id desc limit 1";

                    $apply_remark = $this->purchase_db->query($new_query_sql)->row_array();
                    $apply_remark_list[$value['supplier_code']] = $apply_remark['ext'];
                    if (empty($apply_remark_list[$value['supplier_code']])) {

                    }
                }

                $list[$key]['supplier_source_name'] = getSupplierSource($value['supplier_source']);
                if (isset($apply_remark_list[$value['supplier_code']]) && strpos($apply_remark_list[$value['supplier_code']], '申请备注')) {
                    $list[$key]['contract_notice'] = $apply_remark_list[$value['supplier_code']];
                }
                //拜访申请人
                $list[$key]['apply_sector']  = !empty($value['apply_sector'])?show_supplier_visit_depart($value['apply_sector']):'';
                //拜访状态

                $visit_status = $this->visit_supplier_status($value);
                $list[$key]['visit_status'] = !empty($visit_status)?show_supplier_visit_status($visit_status):'';



                if (!empty($value['visit_aim'])) {
                    $aim_str = '';
                    $aim_list =explode(',',$value['visit_aim']);
                    foreach ($aim_list as $aim) {
                        $aim_str.= show_supplier_visit_aim($aim).',';

                    }

                    $list[$key]['visit_aim']= trim($aim_str,',');

                }

                //获取上传报告时间
                $report_info = $this->purchase_db->select('create_time')->from('supplier_visit_report')->where('visit_id',$value['visit_id'])->order_by('id','desc')->limit(1)->get()->row_array();
                $list[$key]['report_time']  = !empty($report_info['create_time'])?$report_info['create_time']:'';
                $list[$key]['visit_times']  = $visit_info[$value['supplier_code']]??[];





//
//                $settlement = $this->get_settlement_name($value['supplier_code']);
//                if (!empty($settlement)) {
//                    $settlement_name = implode(',', array_column($settlement, 'settlement_name'));
//                    $list[$key]['supplier_settlement'] = $settlement_name;
//                } else {
//                    $list[$key]['supplier_settlement'] = '';
//                }
                //三月合作金额判断
                $list[$key]['cooperation_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['cooperation_amount'] : 0;
                $list[$key]['payment_days_online_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['payment_days_online_amount'] : 0;
                $list[$key]['payment_days_offline_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['payment_days_offline_amount'] : 0;
                $list[$key]['no_payment_days_amount'] = isset($cooperation_amount[$value['supplier_code']]) ? $cooperation_amount[$value['supplier_code']]['no_payment_days_amount'] : 0;


                $list[$key]['cooperation_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['cooperation_amount'] : 0;
                $list[$key]['payment_days_online_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['payment_days_online_amount'] : 0;
                $list[$key]['payment_days_offline_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['payment_days_offline_amount'] : 0;
                $list[$key]['no_payment_days_amount_one_month'] = isset($cooperation_amount_one_month[$value['supplier_code']]) ? $cooperation_amount_one_month[$value['supplier_code']]['no_payment_days_amount'] : 0;




//                if(isset($cooperation_amount[$value['supplier_code']])){
//                if (isset($params['max_nineth_data']) && strlen($params['max_nineth_data']) > 0 && $list[$key]['cooperation_amount'] > $params['max_nineth_data']) {
//                    unset($list[$key]);
//                }
//                if (isset($params['min_nineth_data']) && strlen($params['min_nineth_data']) > 0 && $list[$key]['cooperation_amount'] < $params['min_nineth_data']) {
//                    if (isset($list[$key]))
//                        unset($list[$key]);
//                }
//                }
                //关联供应商显示
                $relation_info_show = [];
                $relation_info = $this->get_relation_supplier_detail($value['supplier_code'],false);
                if (!empty($relation_info)) {
                    foreach ($relation_info as $re_info) {
                        $relation_info_show[] = $re_info;

                    }

                }


                $list[$key]['relation_info'] = $relation_info_show;
            }
        }

        return array(
            'list' => $list,
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $params['limit']

            ]
        );

    }

    //拜访供应商答案配置
    public function get_visit_answer_config()
    {
        return [
            //与易佰合作时间
            'cooperation_time'=>[
            'name'=>'与易佰合作时间',
            'choice'=>
              [
                'A'=>['desc'=>'3年以上','goal'=>3],
                'B'=>['desc'=>'1-3年','goal'=>2],
                'C'=>['desc'=>'未满1年','goal'=>1],

               ]],

            //每月平均入库金额(RMB)
            'average_monthly_storage_amount'=>[
                'name'=>'每月平均入库金额(RMB)',
                'choice'=>[
                'A'=>['desc'=>'>=500万','goal'=>6],
                'B'=>['desc'=>'500万>X>=200万','goal'=>5],
                'C'=>['desc'=>'200万>X>=50万','goal'=>4],
                'D'=>['desc'=>'50万>X>=10万','goal'=>3],
                'E'=>['desc'=>'10万>X>=1万','goal'=>2],
                'F'=>['desc'=>'X<1万','goal'=>1]

              ]
            ],
            //到货准确率?(可参考智库数据)
            'arrival_accuracy_rate'=>[
                'name'=>'到货准确率?(可参考智库数据)',
                'choice'=> [
                'A'=>['desc'=>'X≥90%','goal'=>6],
                'B'=>['desc'=>'90%＞X≥80%','goal'=>5],
                'C'=>['desc'=>'80%＞X≥60%','goal'=>4],
                'D'=>['desc'=>'60%＞X≥50%','goal'=>2],
                'E'=>['desc'=>'X＜50%','goal'=>1]

            ]],
            //生产计划是否受季节影响
            'production_affected_by_season'=>[
                'name'=>'生产计划是否受季节影响',
                'choice'=>[
                'A'=>['desc'=>'不影响','goal'=>10],
                'B'=>['desc'=>'受季节影响,不受淡旺季影响','goal'=>7],
                'C'=>['desc'=>'受淡旺季影响','goal'=>4],
                'D'=>['desc'=>'断续经营','goal'=>0]
            ]],
            //正常交货周期是多少天?
            'delivery_period'=>[
                'name'=>'正常交货周期是多少天?',
                'choice'=>[
                'A'=>['desc'=>'X≤7天','goal'=>5],
                'B'=>['desc'=>'7天＜X≤10天','goal'=>4],
                'C'=>['desc'=>'10天＜X≤15天','goal'=>3],
                'D'=>['desc'=>'15天＜X≤30天','goal'=>2],
                'E'=>['desc'=>'30天＜X≤45天','goal'=>1],
                'F'=>['desc'=>'X＞45天','goal'=>0]
            ]],
            //目前合作账期
            'cooperation_account_period'=>[
                'name'=>'目前合作账期',
                'choice'=>
                [
                'A'=>['desc'=>'线下月结90天','goal'=>5],
                'B'=>['desc'=>'线下月结60天','goal'=>4],
                'C'=>['desc'=>'线下月结30天','goal'=>3],
                'D'=>['desc'=>'线下月结15天','goal'=>2],
                'E'=>['desc'=>'线下半月结','goal'=>1],
                'F'=>['desc'=>'其他','goal'=>0]
               ]
            ],
            //是否一般纳税人,支持美金收款
            'support_dollar'=>[
                'name'=>'是否一般纳税人,支持美金收款?',
                'choice'=> [
                'A'=>['desc'=>'一般纳税人,支持美金收款','goal'=>5],
                'B'=>['desc'=>'一般纳税人','goal'=>4],
                'C'=>['desc'=>'美金收款','goal'=>3],
                'D'=>['desc'=>'其他','goal'=>0],

            ]],
            //是否支持OEM/ODM
            'support_oem_or_odm'=>[
                'name'=>'是否支持OEM/ODM?',
               'choice'=> [
                'A'=>['desc'=>'支持','goal'=>5],
                'B'=>['desc'=>'不支持','goal'=>0],

            ]],
            //易佰在售供应商的自主品牌或授权品牌数量
            'authorized_brands_num'=>[
                'name'=>'易佰在售供应商的自主品牌或授权品牌数量',
                'choice'=>[
                'A'=>['desc'=>'3个及以上','goal'=>5],
                'B'=>['desc'=>'2个','goal'=>3],
                'C'=>['desc'=>'1个','goal'=>1],
                'D'=>['desc'=>'无','goal'=>0],
            ]],

            //易佰在售供应商的私模产品或外观专利产品数量
            'patented_products_num'=>[
                'name'=>'易佰在售供应商的私模产品或外观专利产品数量',
               'choice'=> [
                'A'=>['desc'=>'3个及以上','goal'=>5],
                'B'=>['desc'=>'2个','goal'=>3],
                'C'=>['desc'=>'1个','goal'=>1],
                'D'=>['desc'=>'无','goal'=>0],

            ]],
            //供货给易佰的产品是否有私模或外观专利或海外品牌
            'oversea_brands_num'=>[
                'name'=>'供货给易佰的产品是否有私模或外观专利或海外品牌',
                'choice'=>[
                'A'=>['desc'=>'3个及以上','goal'=>5],
                'B'=>['desc'=>'2个','goal'=>3],
                'C'=>['desc'=>'1个','goal'=>1],
                'D'=>['desc'=>'无','goal'=>0]

            ]],

            //给其他公司贴牌生成的产品数量
            'oem_other_companies_num'=>[
                'name'=>'给其他公司贴牌生成的产品数量',
               'choice'=> [
                'A'=>['desc'=>'3个及以上','goal'=>5],
                'B'=>['desc'=>'2个','goal'=>3],
                'C'=>['desc'=>'1个','goal'=>1],
                'D'=>['desc'=>'无','goal'=>0]
            ]],
            //供应商类型
            'supplier_type'=>[
                'name'=>'供应商类型',
                'choice'=> [
                'A'=>['desc'=>'生产商','goal'=>2],
                'B'=>['desc'=>'代理商','goal'=>1],
                'C'=>['desc'=>'贸易商','goal'=>0]

            ]],
            //供应商主要客户
            'major_client'=>[
                'name'=>'供应商主要客户',
                'choice'=>
                [
                'A'=>['desc'=>'日本,欧美','goal'=>3],
                'B'=>['desc'=>'一般外贸','goal'=>2],
                'C'=>['desc'=>'国内','goal'=>1]

            ]],
            //经营规模
            'business_scale'=>[
                'name'=>'经营规模',
                'choice'=>
                [
                'A'=>['desc'=>'超大型(500人以上)','goal'=>5],
                'B'=>['desc'=>'大型(100人以上)','goal'=>4],
                'C'=>['desc'=>'中型(50-100人)','goal'=>3],
                'D'=>['desc'=>'小型(20-50人)','goal'=>2],
                'E'=>['desc'=>'个体户(20人以下)','goal'=>1]

            ]],
            //是否有自主设计或自主研发团队
            'is_design'=>[
                'name'=>'是否有自主设计或自主研发团队',
                'choice'=>
                [
                'A'=>['desc'=>'有','goal'=>5],
                'B'=>['desc'=>'没有','goal'=>0]

            ]],
            //.工厂设备保养状态
            'factory_maintenance_status'=>[
                'name'=>'工厂设备保养状态',
                'choice'=>
                [
                'A'=>['desc'=>'设备整洁保养良好','goal'=>5],
                'B'=>['desc'=>'设备正常运行','goal'=>3],
                'C'=>['desc'=>'待修理状态','goal'=>0]

            ]],
            //是否自有工厂
            'own_factory'=>[
                'name'=>'是否自有工厂',
                'choice'=>
                [
                'A'=>['desc'=>'自有工厂,不委外加工','goal'=>5],
                'B'=>['desc'=>'非关键技术委外加工','goal'=>4],
                'C'=>['desc'=>'指定唯一代加工厂','goal'=>3],
                'D'=>['desc'=>'有多个代加工厂','goal'=>1]

            ]],

            //原材料备料情况
            'raw_material_situation'=>[
                'name'=>'原材料备料情况',
                'choice'=>
                [
                'A'=>['desc'=>'常备1月物料库存','goal'=>5],
                'B'=>['desc'=>'常备1周物料库存','goal'=>3],
                'C'=>['desc'=>'无备料库存','goal'=>0]]

            ],

            //日均产能
            'average_daily_production_capacity'=>[
                'name'=>'日均产能',
                'choice'=>[
                'A'=>['desc'=>'1万以上','goal'=>5],
                'B'=>['desc'=>'1千以上','goal'=>3],
                'C'=>['desc'=>'1千以下','goal'=>1],
                'D'=>['desc'=>'其他','goal'=>0]

            ]],


        ];

    }

    //拜访申请
    public function apply_visit($params)
    {
        $result = ['code'=>false,'msg'=>''];

        try{
            if (!empty($params)) {

                foreach ($params as $key=>$param) {

                    if ($key!='remark'&&empty($param)&&$key!='visit_id'&&$key!='phone_backup') {
                       throw new Exception( '必填字段缺失，请检查');


                    }

                }
                if ($params['visit_id']) {//编辑
                    $update_data = $params;
                    $update_data['edit_id'] =getActiveUserId();
                    $update_data['edit_name'] = getActiveUserName();
                    $update_data['audit_status'] = 1;
                    $update_data['visit_status'] = SUPPLIER_VISIT_WAIT_AUDIT;//再次编辑需要重新审核


                    unset($update_data['visit_id']);
                    $flag = $this->purchase_db->where('id',$params['visit_id'])->update($this->supplier_visit_table ,$update_data);

                    operatorLogInsert(
                        [
                            'id'      => $update_data['supplier_code'],
                            'type'    => 'supplier_visit',
                            'content' => "编辑供应商",
                            'detail'  => '申请备注'.$params['remark'],
                            'operate_type' => 2
                        ]
                    );




                } else {
                    $add_data = $params;
                    unset($add_data['visit_id']);
                    $add_data['apply_no'] = get_prefix_new_number('BF'.substr(date("Ymd"),2),1,3);
                    $add_data['create_user_id'] = getActiveUserId();
                    $add_data['create_user_name'] = getActiveUserName();
                    $add_data['create_time']      = date('Y-m-d H:i:s');
                    $flag = $this->purchase_db->insert($this->supplier_visit_table,$add_data);
                    if($flag){


                        $agent_id  = 193670347;
                        $supplier_name = $this->purchase_db->from("supplier")->where("supplier_code",$add_data['supplier_code'])->select("supplier_name")
                            ->get()->row_array();
                        $msg = "供应商:".$supplier_name['supplier_name']."的外出拜访报告已生成,可点击查看";

                        $pushIds = $this->purchase_db->from("supplier_visit_user")->where("template_type",1)->where("is_show",1)
                            ->select("user_number")
                            ->get()->result_array();
                        if(!empty($pushIds)) {
                            $pushIds = array_column($pushIds,"user_number");
                        $userNumbers = implode(",",$pushIds);
                        $url = "http://dingtalk.yibainetwork.com/personalnews/Personal_news/personalNews?agent_id=".$agent_id."&userNumber={$userNumbers}&msg=".$msg;
                        getCurlData($url, '', 'GET');
                        }
                    }


                    operatorLogInsert(
                        [
                            'id'      => $add_data['supplier_code'],
                            'type'    => 'supplier_visit',
                            'content' => "拜访申请",
                            'detail'  => '申请备注'.$params['remark'],
                            'operate_type' => 1
                        ]
                    );

                }

                if (!$flag) {
                    throw new Exception( '操作失败');


                }
                $result['code'] = true;



            } else {
                throw new Exception( '参数为空');


            }

        }catch(Exception $e) {
            $result['msg'] = $e->getMessage();

        }



        return $result;

    }

    //拜访供应商审核列表
    public function visit_supplier_audit_list($params,$offset,$limit,$page=1)
    {

        $this->load->model('user/Purchase_user_model', 'userModel');
        $this->load->model('supplier/Supplier_address_model');


        $type_arr = [1=>'贸易商',2=>'工厂'];
        $cate_arr = [1=>'单一类目',2=>'交叉类目'];
        $audit_status_arr = [1=>'经理审核',2=>'部门负责人审核',3=>'驳回',4=>'审核通过'];
        $departs_arr = show_supplier_visit_depart();
        $visit_aim_arr = show_supplier_visit_aim();

        $params = $this->table_query_filter($params);


        $query_builder = $this->purchase_db;

        //查询字段
        $fields = 'A.*,supplier.supplier_name,';

        $query_builder->select($fields)
            ->from($this->supplier_visit_table . ' AS A')
            ->join($this->table_name.' AS supplier','supplier.supplier_code=A.supplier_code');





        if (isset($params['audit_status']) && !empty($params['audit_status'])) {

            $query_builder->where('A.audit_status', $params['audit_status']);

        }

        if (isset($params['apply_no']) && !empty($params['apply_no'])) {

            $query_builder->where('A.apply_no', $params['apply_no']);

        }


        if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
            if (is_array($params['supplier_code'])) {
                $query_builder->where_in('A.supplier_code', $params['supplier_code']);
            } else {
                $query_builder->where('A.supplier_code', $params['supplier_code']);
            }
        }

        if (isset($params['visit_start_time_start']) && !empty($params['visit_start_time_start'])) {

            $query_builder->where('A.start_time>=', $params['visit_start_time_start']);

        }

        if (isset($params['visit_start_time_end']) && !empty($params['visit_start_time_end'])) {

            $query_builder->where('A.start_time<=', $params['visit_start_time_end']);

        }

        if (isset($params['visit_end_time_start']) && !empty($params['visit_end_time_start'])) {

            $query_builder->where('A.end_time>=', $params['visit_end_time_start']);

        }

        if (isset($params['visit_end_time_end']) && !empty($params['visit_end_time_end'])) {

            $query_builder->where('A.end_time<=', $params['visit_end_time_end']);

        }

        if (isset($params['visit_aim']) && !empty($params['visit_aim'])) {
            $query_builder->group_start();

            foreach ($params['visit_aim'] as $aim_id) {

                $query_builder ->or_where("FIND_IN_SET({$aim_id},A.visit_aim) !=",0);



            }

            $query_builder->group_end();

        }




        if (isset($params['apply_id']) && !empty($params['apply_id'])) {

            $query_builder->where_in('A.apply_id', $params['apply_id']);

        }

    //申请时间
        if (isset($params['create_time_start']) && !empty($params['create_time_start'])) {

            $query_builder->where('A.create_time>=', $params['apply_time_start']);

        }

        if (isset($params['create_time_end']) && !empty($params['create_time_end'])) {

            $query_builder->where('A.create_time<=', $params['create_time_end']);

        }

        if (isset($params['participants']) && !empty($params['participants'])) {

            foreach ($params['participants'] as $part_id) {
                $query_builder ->where("FIND_IN_SET({$part_id},A.participants) !=",0);




            }

        }


        if (!empty($params['participating_sector'])) {
            foreach ($params['participating_sector'] as $sector_id) {
                $query_builder ->where("FIND_IN_SET({$sector_id},A.participating_sector) !=",0);


            }

        }

        if (!empty($params['supplier_visit_depart'])) {
            $query_builder->where_in('A.apply_sector',$params['supplier_visit_depart'] );


        }




        if (isset($params['province']) && !empty($params['province'])) {

            $query_builder->where('A.province', $params['province']);

        }

        if (isset($params['city']) && !empty($params['city'])) {

            $query_builder->where('A.city', $params['city']);

        }

        if (isset($params['area']) && !empty($params['area'])) {

            $query_builder->where('A.city', $params['city']);

        }






        $clone_db = clone($query_builder);
        $total=$clone_db->count_all_results();//符合当前查询条件的总记录数


        // 3.分页参数

        $list = $query_builder->order_by('A.id DESC')->limit($limit,$offset)->get()->result_array();







        if ($list)
            foreach ($list as $key=>$value) {
                $list[$key]['audit_status'] = $audit_status_arr[$value['audit_status']];
                $list[$key]['cate_attr'] = $cate_arr[$value['cate_attr']];
                $list[$key]['apply_sector'] = $departs_arr[$value['apply_sector']];


                if (!empty($value['visit_aim'])) {
                    $aim_str = '';
                    $aim_list =explode(',',$value['visit_aim']);
                    foreach ($aim_list as $aim) {
                        $aim_str.= $visit_aim_arr[$aim].',';

                    }

                    $list[$key]['visit_aim']= trim($aim_str,',');

                }



                $list[$key]['type'] = $type_arr[$value['type']];

                $list[$key]['province']  = $this->Supplier_address_model->get_address_name_by_id($value['province']);
                $list[$key]['city']  = $this->Supplier_address_model->get_address_name_by_id($value['city']);
                $list[$key]['area']  = $this->Supplier_address_model->get_address_name_by_id($value['area']);



                if (!empty($value['participating_sector'])) {
                    $sector_names = '';
                    $participating_sector = explode(',',$value['participating_sector']);
                    foreach ($participating_sector as $sector_id) {
                        $sector_names.= $departs_arr[$sector_id].',';

                   }

                    $list[$key]['participating_sector'] = trim($sector_names,',');



                }

        }




        $return_data = [
            'data_list'   => [
                'value' => $list,
                'key' =>  [
                    "序号",
                    "审核状态",
                    "申请编号",
                    "供应商代码",
                    "供应商名称",
                    "公司类型",
                    "类目属性",
                    "申请人",
                    "申请部门",
                    "参与人",
                    "参与部门",
                    "申请时间",
                    "出发时间",
                    "结束时间",
                    "拜访目的",
                    "拜访地址",
                    "审核备注",
                ],

                'drop_down_box' =>[
                    'type_arr'=>$type_arr,
                    'apply_id'=>$this->userModel->getCompanyAllPerson(),
                    'audit_status_arr'=>$audit_status_arr,
                    'cate_arr'=>$cate_arr,
                    'departs_arr'=>$departs_arr,
                    'visit_aim_arr'=>$visit_aim_arr

                ],

            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit

            ],

        ];

        return $return_data;




    }

    public function audit_visit_supplier($id,$status,$remark)
    {
        $result = ['code'=>false,'msg'=>''];
        $audit_user_name = getActiveUserName();
        $audit_user_id   = getActiveUserId();
        $audit_info = $this->get_audit_visit_info($id,false);

        //必须同一个部门才能审核
        try{
            if (!in_array($audit_info['audit_status'],[1,2])) {
                throw new Exception($audit_info['apply_no'].'审核状态不为待审核');

            }

            if (empty($status)) {
                throw new Exception($audit_info['apply_no'].'请传审核状态');

            }

            if ($audit_info['audit_status'] == 1) {
                $operate_type = 3;
            } else {
                $operate_type = 4;
            }

            if ($status == 1) {//审核通过
                $update_info  = [];
                if ($audit_info['audit_status'] == 1) {
                    $update_info['audit_status'] = 2;
                    $update_info['audit_user_manage'] = $audit_user_name;
                    $update_info['audit_id_manage'] = $audit_user_id;
                    $update_info['remark']   = $remark;


                } else {
                    $update_info['audit_status'] = 4;
                    $update_info['audit_user_director'] = $audit_user_name;
                    $update_info['audit_id_director'] = $audit_user_id;
                    $update_info['remark']   = $remark;
                    $update_info['visit_status']   = SUPPLIER_VISIT_AUDIT_PASS;



                }

                //发送消息
                $this->Message_model->AcceptMessage('visit_audit',['data'=>[$id],'message'=>$audit_info['apply_name'].'申请拜访供应商'.$audit_info['supplier_name'].'拜访目的:'.$audit_info['visit_aim_name'].'已审核通过，出发日期:'.$audit_info['start_time'],'user'=>getActiveUserName(),'type'=>'部门负责人审核通过']);


                operatorLogInsert(
                    [
                        'id'      => $audit_info['supplier_code'],
                        'type'    => 'supplier_visit',
                        'content' => "审核通过",
                        'detail'  => '审核备注：'.$remark,
                        'operate_type' => $operate_type
                    ]
                );

                // 发送钉钉消息

                $agent_id  = 193670347;
                $visitDatas = $this->purchase_db->from("supplier_visit_apply")->where('id',$id)->get()->row_array();
                $supplier_name = $this->purchase_db->from("supplier")->where("supplier_code",$visitDatas['supplier_code'])->select("supplier_name")
                    ->get()->row_array();

               /* $visitAimString = "";
                $aim_list =explode(',',$visitDatas['visit_aim']);
                foreach ($aim_list as $aim) {
                    $visitAimString.= show_supplier_visit_aim($aim).',';

                }*/
                $msg = $visitDatas['apply_name']."申请拜访供应商:".$supplier_name['supplier_name'].",拜访目的:".$audit_info['visit_aim_name'].".已经审核通过,出发日期:".$visitDatas['start_time'];
                $pushIds[] = getUserNumberById($visitDatas['apply_id']);
                $pushIds[] = getUserNumberById($visitDatas['audit_id_manage']);
                $participantsDatas = explode(",",$visitDatas['participants']);
                foreach($participantsDatas as $participantsData_value){
                    $pushIds[] = getUserNumberById($participantsData_value);
                }
                $userNumbers = implode(",",$pushIds);
                $url = "http://dingtalk.yibainetwork.com/personalnews/Personal_news/personalNews?agent_id=".$agent_id."&userNumber={$userNumbers}&msg=".$msg;
                getCurlData($url, '', 'GET');

            } else {//驳回

                if ($audit_info['audit_status'] == 1) {
                    $update_info['audit_user_manage'] = $audit_user_name;
                    $update_info['audit_id_manage'] = $audit_user_id;
                } else {
                    $update_info['audit_user_director'] = $audit_user_name;
                    $update_info['audit_id_director'] = $audit_user_id;

                }

                $update_info['audit_status'] = 3;
                $update_info['remark']   = $remark;
                $update_info['visit_status']   = SUPPLIER_VISIT_REJECT;


                operatorLogInsert(
                    [
                        'id'      => $audit_info['supplier_code'],
                        'type'    => 'supplier_visit',
                        'content' => "审核不通过",
                        'detail'  => '驳回原因：'.$remark,
                        'operate_type' => $operate_type
                    ]
                );

                // 发送钉钉消息 apply_id
                $agent_id  = 193670347;
                $visitDatas = $this->purchase_db->from("supplier_visit_table")->where('id',$id)->get()->row_array();
                $supplier_name = $this->purchase_db->from("supplier")->where("supplier_code",$visitDatas['supplier_code'])->select("supplier_name")
                    ->get()->row_array();
                $msg = "您申请拜访供应商:".$supplier_name['supplier_name']."审核被驳回,请及时处理";
                $userNumbers = getUserNumberById($visitDatas['apply_id']);
                $url = "http://dingtalk.yibainetwork.com/personalnews/Personal_news/personalNews?agent_id=".$agent_id."&userNumber={$userNumbers}&msg=".$msg;
                getCurlData($url, '', 'GET');
            }

            $this->purchase_db->where('id',$id)->update($this->supplier_visit_table,$update_info);
            $result['code'] = true;

        }catch (Exception $e) {
            $result['msg'] = $e->getMessage();


        }

        return $result;




    }

    //获取拜访供应商申请信息
    public function get_audit_visit_info($id,$append=false)
    {
        $this->load->model('supplier/Supplier_address_model');
        $type_arr = [1=>'贸易商',2=>'工厂'];
        $cate_arr = [1=>'单一类目',2=>'交叉类目'];
        $audit_status_arr = [1=>'经理审核',2=>'部门负责人审核',3=>'驳回',4=>'审核通过'];
        $visit_status_arr = show_supplier_visit_status();
        $departs_arr = show_supplier_visit_depart();
        $visit_aim_arr = show_supplier_visit_aim();
        $visit_info = $this->purchase_db->select('apply.*,supplier.supplier_name,supplier.supplier_grade,supplier.supplier_level,supplier.register_date,supplier.register_address,supplier.create_time as supplier_create_time,supplier.create_user_name,supplier.ship_province,supplier.ship_city,supplier.ship_area,supplier.ship_address')->from('supplier_visit_apply apply')->join('supplier supplier','supplier.supplier_code=apply.supplier_code','left')
            ->where('apply.id',$id)->get()->row_array();
        if (!empty($visit_info)) {
            //一些信息替换成中文
            $visit_info['supplier_level'] = getSupplierLevel($visit_info['supplier_level']);
            $visit_info['audit_status_name'] = $audit_status_arr[$visit_info['audit_status']];
            $visit_info['type_name'] = $type_arr[$visit_info['type']];
            $visit_info['cate_attr_name'] = $cate_arr[$visit_info['cate_attr']];
            $visit_info['apply_sector_name'] = $departs_arr[$visit_info['apply_sector']];
            $visit_info['province_name']  = $this->Supplier_address_model->get_address_name_by_id($visit_info['province']);
            $visit_info['city_name']  = $this->Supplier_address_model->get_address_name_by_id($visit_info['city']);
            $visit_info['area_name']  = $this->Supplier_address_model->get_address_name_by_id($visit_info['area']);
            $visit_status = $this->visit_supplier_status($visit_info);
            $visit_info['visit_status'] = $visit_status;
            $visit_status_name = $visit_status_arr[$visit_status]??'';
            $visit_info['visit_status_name'] = $visit_status_name;


            if (!empty($visit_info['ship_province'])) {
                $visit_info['ship_province'] = $this->addressModel->get_address_name_by_id($visit_info['ship_province']);


            }

            if (!empty($visit_info['ship_city'])) {
                $visit_info['ship_city'] = $this->addressModel->get_address_name_by_id($visit_info['ship_city']);


            }

            if (!empty($visit_info['ship_area'])) {
                $visit_info['ship_area'] = $this->addressModel->get_address_name_by_id($visit_info['ship_area']);

            }



            //参与部门
            if (!empty($visit_info['participating_sector'])) {
                $sector_names = '';
                $participating_sector_arr =explode(',',$visit_info['participating_sector']);
                foreach ($participating_sector_arr as $sector_id) {
                    $sector_names.= $departs_arr[$sector_id].',';

                }

                $visit_info['participating_sector_name'] = trim($sector_names,',');


            }

            if (!empty($visit_info['visit_aim'])) {
                $aim_str = '';
                $aim_list =explode(',',$visit_info['visit_aim']);
                foreach ($aim_list as $aim) {
                    $aim_str.= $visit_aim_arr[$aim].',';

                }

                $visit_info['visit_aim_name'] = trim($aim_str,',');


            }
            if ($append) {//提交报告信息和上传图片信息
                $report_info = $this->purchase_db->select('*')->from('supplier_visit_report')->where('visit_id',$visit_info['id'])->order_by('id','desc')->limit(1)->get()->row_array();
                if (!empty($report_info)) {
                    $report_info['answer'] = json_decode($report_info['answer'],true);
                    $report_info['improve_situation'] = str_replace(array("\r\n", "\r", "\n"), '<br/>', $report_info['improve_situation']);
                    $report_info['improve_situation'] = json_decode(str_replace(array("\t"), '', $report_info['improve_situation']),true);



                    $images_list =     $this->purchase_db->select('group_concat(image_url)  as images_path,type')->from('supplier_visit_images')->where('report_id',$report_info['id'])->group_by('type')->get()->result_array();
                    if (!empty($images_list)) {
                        $images_list = array_column($images_list,'images_path','type');
                        $report_info['supplier_image'] = $images_list['supplier_image']??'';
                        $report_info['honor_certificate'] = $images_list['honor_certificate']??'';
                        $report_info['product_line'] = $images_list['product_line']??'';

                    } else {
                        $report_info['supplier_image'] = '';
                        $report_info['honor_certificate'] = '';
                        $report_info['product_line'] = '';


                    }

                }
                $report_info['conclusion'] = str_replace(array("\r\n", "\r", "\n"), '<br/>', $report_info['conclusion']);
                $report_info['conclusion'] = str_replace(array( "\t"), '', $report_info['conclusion']);


                $visit_info['report_info']=$report_info;

            }



        }
        return $visit_info;


    }


    //返回拜访申请审核状态值
    public function visit_supplier_status($audit_info)
    {
        $now_time  = time();
        //如果审核通过后，状态值根据时间变化
        if ($audit_info['visit_status'] == SUPPLIER_VISIT_AUDIT_PASS) {
            if ($now_time<=strtotime($audit_info['start_time'])) {
                $status = SUPPLIER_VISIT_WAIT_VISITING;

            } elseif (($now_time<=strtotime($audit_info['end_time']))&&($now_time>=strtotime($audit_info['start_time']))) {
                $status = SUPPLIER_VISIT_IN_VISITING;

            } else{
                $status = SUPPLIER_VISIT_WAIT_REPORT;


            }


        } else {
            $status = $audit_info['visit_status'];

        }

        return $status;


    }


    //上传提交报告
    public function upload_visit_report($visit_id,$params)
    {
        $result = ['code'=>false,'msg'=>''];
        $answer = $params['answer'];

        $improve_situation = $params['improve_situation'];
        $insert_images = [];
        $time=time();
        $total_goals = 0;//总分
        $visit_info  = $this->get_audit_visit_info($visit_id,false);
        try {
            //单选题答案
            if (!is_json($answer)) {
                $result['msg']='单选题数据格式有误';

            }
            $answer_config = $this->get_visit_answer_config();
            $answer_values = json_decode($answer,true);


            foreach ($answer_config as $answer_name=>$answer_value_list) {

                if (empty($answer_values[$answer_name])) {
                    throw new Exception($answer_name.'单选题没有提交');
                }

                $total_goals+=$answer_value_list['choice'][$answer_values[$answer_name]]['goal'];

            }

            //改善信息验证
            if (empty($improve_situation)) {
                throw new Exception('改善报告没有填写');

            } else {
                $improve_situation = json_decode($improve_situation,true);

                $visit_aim_arr= explode(',',$visit_info['visit_aim_name']);

                foreach ($visit_aim_arr as $visit_aim_name) {
                    if (empty($improve_situation[$visit_aim_name]['improve_before'])||empty($improve_situation[$visit_aim_name]['improve_end'])) {
                        throw new Exception('拜访原因'.$visit_aim_name.'改善情况没有填写');


                    }


                }

            }






            if ($total_goals>0) {//计算汇报等级
                $min_time = strtotime(date('Y').'-01-01');
                $sec_time = strtotime(date('Y').'-06-30'.' 23:59:59');
                if ($min_time<=$time&&$time<=$sec_time) {
                    if ((0<=$total_goals)&&($total_goals<50)) {
                        $cal_level = 'D';
                    }elseif((50<=$total_goals)&&($total_goals<65)){
                        $cal_level = 'C';
                    }elseif((65<=$total_goals)&&($total_goals<80)){
                        $cal_level = 'B';
                    }elseif((80<=$total_goals)&&($total_goals<90)){
                        $cal_level = 'A';
                    }elseif($total_goals>=90){
                        $cal_level = 'S';

                    }

                } else {
                    if ((0<=$total_goals)&&($total_goals<60)) {
                        $cal_level = 'D';
                    }elseif((60<=$total_goals)&&($total_goals<70)){
                        $cal_level = 'C';
                    }elseif((70<=$total_goals)&&($total_goals<80)){
                        $cal_level = 'B';
                    }elseif((80<=$total_goals)&&($total_goals<90)){
                        $cal_level = 'A';
                    }elseif($total_goals>=90){
                        $cal_level = 'S';

                    }

                }

            } else {
                throw new Exception('统计分数失败');


            }
            //写入report
            $insert_report = [
                'visit_id'=>$visit_id,
                'user_id'=>getActiveUserId(),
                'user_name'=>getActiveUserName(),
                'answer'=>$answer,
                'percentage_turnover'=>$params['percentage_turnover'],
                'team_company'=>$params['team_company'],
                'certified_product'=>$params['certified_product'],
                'create_time'=>date('Y-m-d H:i:s'),
               /* 'improve_before'=>$params['improve_before'],
                'improve_end'=>$params['improve_end'],*/
                'visit_level'=>$cal_level,
                'visit_grade'=>$total_goals,
                'improve_situation'=>json_encode($improve_situation,JSON_UNESCAPED_UNICODE),
                'conclusion'=>$params['conclusion'],


            ];


            $this->purchase_db->insert($this->supplier_visit_report_table,$insert_report);
            $insert_id = $this->purchase_db->insert_id();
            if (!$insert_id) {
                throw new Exception('汇报上传失败');

            } else {
                $update_apply = [
                    'visit_level'=>$cal_level,
                    'visit_grade'=>$total_goals,
                    'visit_status'=>SUPPLIER_VISIT_END
                ];
                $this->purchase_db->where('id',$visit_id)->update($this->supplier_visit_table,$update_apply);
                if (!empty($params['supplier_image'])) {
                    foreach ($params['supplier_image'] as $url) {
                        $insert_images[] = [
                            'type'=>'supplier_image',
                            'image_url'=>$url,
                            'report_id'=>$insert_id

                        ];
                    }
                }
                if (!empty($params['honor_certificate'])) {
                    foreach ($params['honor_certificate'] as $url) {
                        $insert_images[] = [
                            'type'=>'honor_certificate',
                            'image_url'=>$url,
                            'report_id'=>$insert_id

                        ];

                    }

                }
                if (!empty($params['product_line'])) {
                    foreach ($params['product_line'] as $url) {
                        $insert_images[] = [
                            'type'=>'product_line',
                            'image_url'=>$url,
                            'report_id'=>$insert_id

                        ];

                    }
                }

                if (!empty($insert_images)) {
                    $this->purchase_db->insert_batch('supplier_visit_images',$insert_images);


                }

                $vitisUsers = $this->purchase_db->from("supplier_visit_user")->where("template_type",2)->where("is_show",1)
                    ->select("user_number")->get()->result_array();
                if(!empty($vitisUsers)){
                    $supplierDatas = $this->purchase_db->from("supplier_visit_apply as apply")->join("supplier as supp","apply.supplier_code=supp.supplier_code","LEFT")
                        ->where("apply.id",$visit_id)->select("supplier_name")->get()->row_array();
                    $msg = "供应商:".$supplierDatas['supplier_name']."的外出拜访报告已生成,可点击查看";
                    $agent_id  = 193670347;
                    $pushIds = array_column($vitisUsers,"user_number");
                    $userNumbers = implode(",", $pushIds);
                    $url = "http://dingtalk.yibainetwork.com/personalnews/Personal_news/personalNews?agent_id=" . $agent_id . "&userNumber={$userNumbers}&msg=" . $msg;
                    getCurlData($url, '', 'GET');
                }
                //上传报告成功提示相关人员
               $this->Message_model->AcceptMessage('visit_report',['data'=>[$visit_id],'message'=>'上传拜访报告','user'=>getActiveUserName(),'type'=>'上传拜访报告']);

            }
            operatorLogInsert(
                [
                    'id'      => $visit_info['supplier_code'],
                    'type'    => 'supplier_visit',
                    'content' => "上传报告",
                    'detail'  => '总分:'.$total_goals.",评级:".$cal_level,
                    'operate_type' => 5
                ]
            );


            $result['code']=true;

        }catch(Exception $e){
            $result['msg'] = $e->getMessage();

        }
        return $result;

    }



    /**
     * 获取省
     * @return array
     * @author Jaxton 2019/01/23
     */
    public function get_province(){
        $list=$this->purchase_db->select('id,region_name,region_code')->from('region')
            ->where('region_type',REGION_TYPE_PROVINCE)
            ->get()->result_array();
        $new_data=[];
        if(!empty($list)){
            foreach($list as $k => $v){
                $new_data[$v['region_code']]=$v['region_name'];
            }
        }
        return $new_data;
    }


    //获取多个供应商的访问次数
    public function get_visit_times($supplier_codes=[])
    {
        //获取供应商的拜访次数
        $info = [];

        if (!empty($supplier_codes)) {
            foreach ($supplier_codes as $supplier_code ) {
                $info[$supplier_code] = ['times'=>0,'is_all_end'=>0];

            }

        }

        $result = $this->purchase_db->select('*')->from($this->supplier_visit_table)->where_in('supplier_code',$supplier_codes)->where('visit_status>=',SUPPLIER_VISIT_AUDIT_PASS)->get()->result_array();
        if (!empty($result)) {
            foreach ($result as $item) {
                if (in_array($item['supplier_code'],$supplier_codes)) {
                    $info[$item['supplier_code']]['times']+=1;
                    if ($item['visit_status']!=SUPPLIER_VISIT_END) {
                        $info[$item['supplier_code']]['is_all_end'] = 1;

                    }

                }

            }

        }
        return $info;

    }


    public function update_postage_supplier_sku($supplier_code)
    {
        return $this->purchase_db->where('supplier_code', $supplier_code)->update('pur_product', ['is_shipping' => 1]);
    }

    //检查财务结算信息是否优化
    public function check_payment_optimization($now_settlement,$history_payment_info,$change_payment_info)
    {
        //优化列表
        $old_settlement = [];
        $optimization_list = [10,20,17,18,19,30,31,32,39,40,1,27,37,8,9,6,38];
        $negative = 0;//负优化的条数
        $is_optimization = 0;//没有优化
        $now_num = count($now_settlement);
        if (!empty($history_payment_info)) {
            foreach ($history_payment_info as $is_tax => $item ) {
                foreach ($item as  $purchase_type_id=> $value){
                    $old_settlement[] = $value['supplier_settlement'];
                }

            }

        }
        $history_num = count(array_unique($old_settlement));
        if ($history_num == 1&&$now_num>=2) {
            $is_optimization = 2;//负优化

        }elseif($now_num == 1&&$history_num>=2) {
            $is_optimization = 1;//正优化

        }elseif($now_num>=2&&$history_num>=2){
            foreach ($change_payment_info as $is_tax => $item ) {
                foreach ($item as  $purchase_type_id=> $value){
                    $old_payment_info = $history_payment_info[$is_tax][$purchase_type_id]??[];
                    if (!empty($old_payment_info)&&$old_payment_info['supplier_settlement']) {
                        if ($old_payment_info['supplier_settlement']!=$value['supplier_settlement']&&in_array($old_payment_info['supplier_settlement'],$optimization_list)&&in_array($value['supplier_settlement'],$optimization_list)) {
                            $old_index =array_search($old_payment_info['supplier_settlement'],$optimization_list);
                            $new_index =array_search($value['supplier_settlement'],$optimization_list);

                            if ($new_index>$old_index) {//正向优化
                                $is_optimization = 1;

                            } else {
                                $negative++;
                            }



                        }

                    }

                }


            }


        }
        //没有正向优化，全是负向优化
        if ($is_optimization ==0 &&$negative>0) {
            $is_optimization = 2;

        }

        return $is_optimization;

    }

    /**
     * 添加 拜访供应商通知人 pur_supplier_visit_user
     * @author:luxu
     * @time:2021年8月24号
     **/

    public function add_supplier_users($datas){

        if(!empty($datas)){


            $result = $this->purchase_db->insert_batch('supplier_visit_user',$datas);
            if($result){
                return True;
            }
            return False;
        }

        return NULL;
    }

    public function upd_supplier_users($datas){

        foreach($datas as $key=>$value){

            $update = [

                'template_type' =>$value['template_type'],
                'update_time' => date("Y-m-d H:i:s",time()),
                'update_user_name' => getActiveUserName(),
                'username' => $value['username']
            ];

            $this->purchase_db->where("id",$value['id'])->update("supplier_visit_user",$update);
        }
    }

   public function del_supplier_users($id,$type){

        if($type == 'del'){

            $result = $this->purchase_db->where("id",$id)->delete("supplier_visit_user");
        }

        if($type == 1){

           $result = $this->purchase_db->where("id",$id)->update("supplier_visit_user",['is_show'=>2,'update_time'=>date("Y-m-d H:i:s",time()),'update_user_name'=>getActiveUserName()]);
        }
        if($type == 2){
            $result = $this->purchase_db->where("id",$id)->update("supplier_visit_user",['is_show'=>1,'update_time'=>date("Y-m-d H:i:s",time()),'update_user_name'=>getActiveUserName()]);
        }
        if($result){

            return True;
        }
        return False;
   }

   /**
    * 查询拜访供应商通知人
    * @author:luxu
    * @time:2021年8月25号
    **/
   public function show_supplier_users(){

      $result = $this->purchase_db->from("supplier_visit_user")->get()->result_array();
      if(!empty($result)){

          return $result;
      }
      return NULL;
   }










}